<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Http\Concerns\BuildsRunContext;
use LimenAi\Runtime\RunContextData;
use LimenAi\Http\Services\ConversationAccessGuard;
use LimenAi\Observability\MessageUsageEnvelope;

class MessageController
{
    use BuildsRunContext;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentRepository $agents,
        private readonly AgentRunDispatcher $dispatcher,
        private readonly AuthorizationService $authorization,
        private readonly ConversationAccessGuard $accessGuard,
        private readonly RunRepository $runs,
        private readonly UsageReader $usageReader,
        private readonly ConfigRepository $config,
    ) {}

    public function store(Request $request, string $conversationId): JsonResponse
    {
        abort_unless(
            $this->accessGuard->canAccess($request->user(), $conversationId),
            403,
            'Conversation access denied.',
        );

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'attachments' => ['sometimes', 'array', 'max:'.(int) config('limen-ai.ui.max_attachments', 5)],
            'attachments.*.type' => ['required_with:attachments', 'string'],
            'attachments.*.url' => ['sometimes', 'string'],
            'attachments.*.text' => ['sometimes', 'string'],
            'attachments.*.image_url' => ['sometimes', 'array'],
        ]);

        $conversation = $this->conversations->find($conversationId);

        abort_if($conversation === null, 404, 'Conversation not found.');

        $agentKey = (string) ($conversation['agent_key'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);

        $context = RunContextData::make([
            'user_id' => $this->authorization->currentUserId(),
            'guest_token' => $request->header('X-Limen-Guest-Token'),
            'metadata' => [
                'attachments' => $validated['attachments'] ?? [],
            ],
            'locale' => $request->getPreferredLanguage() ?? app()->getLocale(),
        ]);

        $result = $this->dispatcher->dispatchRun(
            $agentKey,
            $conversationId,
            (string) $validated['message'],
            $context,
        );

        $payload = [
            'queued' => $result->queued,
            'run_id' => $result->runId,
            'status' => $result->queued ? 'queued' : 'running',
        ];

        if (! $result->queued) {
            $run = $this->runs->find($result->runId);
            $metadata = is_array($run['metadata'] ?? null) ? $run['metadata'] : [];
            $usageSummary = $run['usage_summary'] ?? $metadata['usage_summary'] ?? null;

            if (is_array($usageSummary)) {
                $payload['usage'] = MessageUsageEnvelope::fromSummary(
                    $result->runId,
                    (string) ($run['provider'] ?? $agent->provider()),
                    (string) ($run['model'] ?? $agent->model()),
                    $usageSummary,
                    MessageUsageEnvelope::hasEstimatedUsage($this->usageReader->recordsForRun($result->runId)),
                );
            }
        }

        return response()->json($payload, $result->queued ? 202 : 200);
    }
}
