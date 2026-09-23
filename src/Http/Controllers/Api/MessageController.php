<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Http\Concerns\BuildsRunContext;
use LimenAi\Http\Services\ConversationAccessGuard;

class MessageController
{
    use BuildsRunContext;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentRepository $agents,
        private readonly AgentRunDispatcher $dispatcher,
        private readonly AuthorizationService $authorization,
        private readonly ConversationAccessGuard $accessGuard,
        private readonly ConfigRepository $config,
    ) {}

    public function store(Request $request, string $conversationId): JsonResponse
    {
        abort_unless($this->accessGuard->canAccess($request->user(), $conversationId), 403, 'Conversation access denied.');
        $validated = $request->validate(['message' => ['required', 'string', 'max:10000']]);
        $conversation = $this->conversations->find($conversationId);
        abort_if($conversation === null, 404, 'Conversation not found.');
        $agentKey = (string) ($conversation['agent_key'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);
        $context = $this->runContextFromRequest($request, $this->authorization);
        $result = $this->dispatcher->dispatchRun($agentKey, $conversationId, (string) $validated['message'], $context);
        return response()->json(['queued' => $result->queued, 'run_id' => $result->runId, 'status' => $result->queued ? 'queued' : 'running'], $result->queued ? 202 : 200);
    }
}
