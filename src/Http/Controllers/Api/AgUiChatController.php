<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Http\Concerns\BuildsRunContext;
use LimenAi\Http\Concerns\WritesSseStream;
use LimenAi\Http\Protocols\AgUiStreamEncoder;
use LimenAi\Http\Services\ConversationAccessGuard;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** AG-UI event stream for CopilotKit-style clients. */
final class AgUiChatController
{
    use BuildsRunContext;
    use WritesSseStream;

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentRepository $agents,
        private readonly AgentRuntime $runtime,
        private readonly AuthorizationService $authorization,
        private readonly ConversationAccessGuard $accessGuard,
    ) {}

    public function store(Request $request, string $conversationId): StreamedResponse
    {
        abort_unless((bool) config('limen-ai.protocols.ag_ui', false), 404);

        abort_unless(
            $this->accessGuard->canAccess($request->user(), $conversationId),
            403,
            'Conversation access denied.',
        );

        $conversation = $this->conversations->find($conversationId);
        abort_if($conversation === null, 404);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:10000'],
            'messages' => ['nullable', 'array'],
            'threadId' => ['nullable', 'string'],
        ]);

        $message = (string) ($validated['message'] ?? '');

        if ($message === '' && isset($validated['messages'])) {
            $last = collect($validated['messages'])->last();
            $message = is_array($last) ? (string) ($last['content'] ?? '') : '';
        }

        abort_if($message === '', 422, 'Message is required.');

        $agentKey = (string) ($conversation['agent_key'] ?? config('limen-ai.default_agent'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);
        $context = $this->runContextFromRequest($request, $this->authorization);

        $runId = (string) Str::uuid();
        $threadId = (string) ($validated['threadId'] ?? $conversationId);

        return Response::stream(function () use ($agentKey, $conversationId, $message, $context, $runId, $threadId): void {
            $encoder = new AgUiStreamEncoder($runId, $threadId);
            $this->writeSseLines($encoder->runStarted());

            foreach ($this->runtime->stream($agentKey, $conversationId, $message, $context) as $chunk) {
                $this->writeSseLines($encoder->encode($chunk));
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
