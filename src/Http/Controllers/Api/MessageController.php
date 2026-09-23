<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Http\Concerns\AuthorizesConversationAccess;
use LimenAi\Http\Concerns\BuildsRunContext;
use LimenAi\Support\ResponseLanguageResolver;

class MessageController
{
    use AuthorizesConversationAccess;
    use BuildsRunContext;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentRepository $agents,
        private readonly AgentRunDispatcher $dispatcher,
        private readonly AuthorizationService $authorization,
        private readonly ResponseLanguageResolver $languages,
        private readonly ConfigRepository $config,
    ) {}

    public function store(Request $request, string $conversationId): JsonResponse
    {
        $this->authorizeConversationAccess($request, $conversationId);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'attachment_ids' => ['sometimes', 'array'],
            'attachment_ids.*' => ['string'],
            'locale' => ['sometimes', 'string', 'max:12'],
            'language' => ['sometimes', 'string', 'max:12'],
        ]);
        $conversation = $this->conversations->find($conversationId);
        abort_if($conversation === null, 404, 'Conversation not found.');

        $metadata = is_array($conversation['metadata'] ?? null) ? $conversation['metadata'] : [];
        $storedLanguage = is_string($metadata['preferred_language'] ?? null) ? $metadata['preferred_language'] : null;
        $resolvedLocale = $this->languages->resolve(
            $validated['locale'] ?? $validated['language'] ?? $request->header('X-Limen-Locale'),
            (string) $validated['message'],
            $storedLanguage,
            $request->getPreferredLanguage() ?? app()->getLocale(),
        );

        if ($storedLanguage !== $resolvedLocale) {
            $metadata['preferred_language'] = $resolvedLocale;
            $this->conversations->update($conversationId, ['metadata' => $metadata]);
        }

        $agentKey = (string) ($conversation['agent_key'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);
        $context = $this->runContextFromRequest($request, $this->authorization, $metadata)
            ->withMetadata([
                'attachment_ids' => array_values($validated['attachment_ids'] ?? []),
                'preferred_language' => $resolvedLocale,
            ])
            ->withLocale($resolvedLocale);
        $result = $this->dispatcher->dispatchRun($agentKey, $conversationId, (string) $validated['message'], $context);

        return response()->json([
            'queued' => $result->queued,
            'run_id' => $result->runId,
            'status' => $result->queued ? 'queued' : 'running',
            'locale' => $resolvedLocale,
        ], $result->queued ? 202 : 200);
    }
}
