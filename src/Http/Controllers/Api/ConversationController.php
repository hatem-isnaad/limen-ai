<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LimenAi\Agents\AgentProfilePresenter;
use LimenAi\Authorization\GuestSessionService;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Authorization\GuestSessionValidator;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Conversations\ConversationService;
use LimenAi\Conversations\ConversationState;
use LimenAi\Http\Concerns\AuthorizesConversationAccess;
use LimenAi\Http\Concerns\BuildsRunContext;

class ConversationController
{
    use AuthorizesConversationAccess;
    use BuildsRunContext;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly MessageRepository $messages,
        private readonly ConversationService $conversationService,
        private readonly AgentRepository $agents,
        private readonly AuthorizationService $authorization,
        private readonly GuestSessionService $guestSessions,
        private readonly GuestSessionValidator $guestSessionValidator,
        private readonly AgentProfilePresenter $agentProfiles,
        private readonly ConfigRepository $config,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agentKey = $request->string('agent')->toString();
        $agentFilter = $agentKey !== '' ? $agentKey : null;
        $limit = (int) $request->input('limit', 50);

        if ($request->user() !== null) {
            $conversations = $this->conversations->listForUser((int) $request->user()->id, $agentFilter, $limit);
        } else {
            $guestToken = (string) $request->header('X-Limen-Guest-Token', '');
            abort_unless($guestToken !== '' && $this->guestSessionValidator->isValid($guestToken), 401, 'Guest session required.');
            $conversations = $this->conversations->listForGuest($guestToken, $agentFilter, $limit);
        }

        return response()->json([
            'conversations' => array_map(
                fn (array $conversation): array => $this->formatConversationSummary($conversation),
                $conversations,
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent' => ['sometimes', 'string'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile' => ['sometimes', 'array'],
        ]);

        $agentKey = (string) ($validated['agent'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);

        $guestToken = $request->header('X-Limen-Guest-Token');
        $metadata = [];

        if ($request->user() === null && (bool) $this->config->get('limen-ai.ui.guest.enabled', false)) {
            if (($guestToken === null || $guestToken === '') && isset($validated['profile'])) {
                $session = $this->guestSessions->register($validated['profile']);
                $guestToken = $session['guest_token'];
                $metadata['guest_profile'] = $session['profile'];
            }

            abort_unless(
                is_string($guestToken) && $guestToken !== '' && $this->guestSessionValidator->isValid($guestToken),
                422,
                'Guest profile or session token is required.',
            );

            if ($metadata === []) {
                $metadata['guest_profile'] = $this->guestSessions->profile($guestToken) ?? [];
            }
        }

        $conversationId = (string) Str::uuid();
        $context = $this->runContextFromRequest($request, $this->authorization, $metadata, $guestToken);
        $this->conversationService->ensure($conversationId, $agentKey, $context);

        if (! empty($validated['title'])) {
            $this->conversations->update($conversationId, ['title' => $validated['title']]);
        } elseif ($request->user() === null && isset($metadata['guest_profile']['name'])) {
            $this->conversations->update($conversationId, [
                'title' => 'Chat — '.$metadata['guest_profile']['name'],
            ]);
        }

        $conversation = $this->conversations->find($conversationId);

        return response()->json([
            'conversation' => $this->formatConversationSummary($conversation),
            'agent' => $this->agentProfiles->present($agent),
            'guest_token' => $guestToken,
        ], 201);
    }

    public function show(Request $request, string $conversationId): JsonResponse
    {
        $this->authorizeConversationAccess($request, $conversationId);
        $conversation = $this->conversations->find($conversationId);

        $agentKey = (string) ($conversation['agent_key'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);

        return response()->json([
            'conversation' => $this->formatConversationSummary($conversation),
            'messages' => $this->formatMessages($this->conversationService->storedMessages($conversationId)),
            'agent' => $agent ? $this->agentProfiles->present($agent) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $conversation
     * @return array<string, mixed>|null
     */
    protected function formatConversationSummary(?array $conversation): ?array
    {
        if ($conversation === null) {
            return null;
        }

        $conversationId = (string) $conversation['id'];
        $channelPrefix = (string) $this->config->get('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation');
        $storedMessages = $this->messages->forConversation($conversationId, 1);
        $lastMessage = $storedMessages[0] ?? null;
        $metadata = is_array($conversation['metadata'] ?? null) ? $conversation['metadata'] : [];

        return [
            'id' => $conversationId,
            'agent_key' => (string) ($conversation['agent_key'] ?? ''),
            'title' => (string) ($conversation['title'] ?? $this->defaultTitle($metadata)),
            'state' => (string) ($conversation['state'] ?? ConversationState::ACTIVE),
            'channel' => $channelPrefix.'.'.$conversationId,
            'preview' => $lastMessage ? (string) ($lastMessage['content'] ?? '') : null,
            'preferred_language' => is_string($metadata['preferred_language'] ?? null) ? $metadata['preferred_language'] : null,
            'created_at' => $conversation['created_at'] ?? null,
            'updated_at' => $conversation['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function defaultTitle(array $metadata): string
    {
        $name = (string) ($metadata['guest_profile']['name'] ?? '');

        return $name !== '' ? 'Chat — '.$name : 'Conversation';
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    protected function formatMessages(array $messages): array
    {
        $visible = array_values(array_filter(
            $messages,
            static fn (array $message): bool => in_array($message['role'] ?? '', ['user', 'assistant', 'system'], true),
        ));

        return array_map(static fn (array $message): array => [
            'id' => (string) ($message['id'] ?? ''),
            'role' => (string) ($message['role'] ?? 'user'),
            'content' => (string) ($message['content'] ?? ''),
            'created_at' => $message['created_at'] ?? null,
        ], $visible);
    }
}
