<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Conversations\ConversationService;
use LimenAi\Conversations\ConversationState;
use LimenAi\Http\Concerns\BuildsRunContext;
use LimenAi\Http\Services\ConversationAccessGuard;

class ConversationController
{
    use BuildsRunContext;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ConversationService $conversationService,
        private readonly AgentRepository $agents,
        private readonly AuthorizationService $authorization,
        private readonly ConversationAccessGuard $accessGuard,
        private readonly ConfigRepository $config,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $agentKey = (string) $request->input('agent', $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);
        $conversationId = (string) Str::uuid();
        $context = $this->runContextFromRequest($request, $this->authorization);
        $this->conversationService->ensure($conversationId, $agentKey, $context);
        return response()->json(['conversation' => $this->formatConversation($this->conversations->find($conversationId))], 201);
    }

    public function show(Request $request, string $conversationId): JsonResponse
    {
        $this->authorizeConversationAccess($request, $conversationId);
        $conversation = $this->conversations->find($conversationId);
        return response()->json(['conversation' => $this->formatConversation($conversation), 'messages' => $this->formatMessages($this->conversationService->storedMessages($conversationId))]);
    }

    protected function authorizeConversationAccess(Request $request, string $conversationId): void
    {
        abort_unless($this->accessGuard->canAccess($request->user(), $conversationId), 403, 'Conversation access denied.');
    }

    protected function formatConversation(?array $conversation): ?array
    {
        if ($conversation === null) { return null; }
        $channelPrefix = (string) $this->config->get('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation');
        return ['id' => (string) $conversation['id'], 'agent_key' => (string) ($conversation['agent_key'] ?? ''), 'state' => (string) ($conversation['state'] ?? ConversationState::ACTIVE), 'channel' => $channelPrefix.'.'.($conversation['id'] ?? ''), 'created_at' => $conversation['created_at'] ?? null, 'updated_at' => $conversation['updated_at'] ?? null];
    }

    protected function formatMessages(array $messages): array
    {
        return array_map(static fn (array $message): array => ['id' => (string) ($message['id'] ?? ''), 'role' => (string) ($message['role'] ?? 'user'), 'content' => (string) ($message['content'] ?? ''), 'created_at' => $message['created_at'] ?? null], $messages);
    }
}
