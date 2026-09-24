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
use LimenAi\Http\Protocols\VercelAiStreamEncoder;
use LimenAi\Http\Services\ConversationAccessGuard;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Vercel AI SDK–compatible UI message stream (`start`, `text-delta`, `tool-*`, `finish`). */
final class VercelChatController
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
        abort_unless((bool) config('limen-ai.protocols.vercel_chat', false), 404);

        abort_unless(
            $this->accessGuard->canAccess($request->user(), $conversationId),
            403,
            'Conversation access denied.',
        );

        $validated = $request->validate([
            'messages' => ['required', 'array'],
            'message' => ['nullable', 'string', 'max:10000'],
        ]);

        $message = (string) ($validated['message'] ?? '');

        if ($message === '') {
            $last = collect($validated['messages'])->last();
            $message = is_array($last) ? (string) ($last['content'] ?? '') : '';
        }

        abort_if($message === '', 422, 'Message is required.');

        $conversation = $this->conversations->find($conversationId);
        abort_if($conversation === null, 404);

        $agentKey = (string) ($conversation['agent_key'] ?? config('limen-ai.default_agent'));
        $agent = $this->agents->find($agentKey);
        $this->authorization->authorizeAgent($agent);
        $context = $this->runContextFromRequest($request, $this->authorization);

        $messageId = (string) Str::uuid();

        return Response::stream(function () use ($agentKey, $conversationId, $message, $context, $messageId): void {
            $encoder = new VercelAiStreamEncoder($messageId);
            $this->writeSseLines($encoder->start());

            foreach ($this->runtime->stream($agentKey, $conversationId, $message, $context) as $chunk) {
                $this->writeSseLines($encoder->encode($chunk));
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Vercel-AI-UI-Message-Stream' => 'v1',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
