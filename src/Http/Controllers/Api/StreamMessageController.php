<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Http\Concerns\BuildsRunContext;
use LimenAi\Http\Services\ConversationAccessGuard;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Server-sent events endpoint for custom frontends (no bundled UI required).
 */
class StreamMessageController
{
    use BuildsRunContext;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentRepository $agents,
        private readonly AgentRuntime $runtime,
        private readonly AuthorizationService $authorization,
        private readonly ConversationAccessGuard $accessGuard,
        private readonly ConfigRepository $config,
    ) {}

    public function store(Request $request, string $conversationId): StreamedResponse
    {
        abort_unless(
            $this->accessGuard->canAccess($request->user(), $conversationId),
            403,
            'Conversation access denied.',
        );

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $conversation = $this->conversations->find($conversationId);

        abort_if($conversation === null, 404, 'Conversation not found.');

        $agentKey = (string) ($conversation['agent_key'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);

        $context = $this->runContextFromRequest($request, $this->authorization);
        $message = (string) $validated['message'];

        return Response::stream(function () use ($agentKey, $conversationId, $message, $context): void {
            foreach ($this->runtime->stream($agentKey, $conversationId, $message, $context) as $chunk) {
                $payload = [
                    'delta' => $chunk->delta,
                    'done' => $chunk->done,
                ];

                if ($chunk->meta !== []) {
                    $payload = array_merge($payload, $chunk->meta);
                }

                echo 'data: '.json_encode($payload, JSON_THROW_ON_ERROR)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
