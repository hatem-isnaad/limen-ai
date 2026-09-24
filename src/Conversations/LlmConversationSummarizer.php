<?php

namespace LimenAi\Conversations;

use Illuminate\Support\Str;
use LimenAi\Contracts\Conversations\ConversationSummarizer;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\RunContextData;

/** SDK-style long-context compression using a one-shot agent run. */
final class LlmConversationSummarizer implements ConversationSummarizer
{
    public function summarize(string $conversationId, array $messages): ?string
    {
        if (count($messages) < (int) config('limen-ai.conversations.summarize_after', 20)) {
            return null;
        }

        $transcript = collect($messages)
            ->map(fn (array $m): string => strtoupper((string) ($m['role'] ?? 'user')).': '.(is_string($m['content'] ?? null) ? $m['content'] : json_encode($m['content'])))
            ->implode("\n");

        $agentKey = (string) config('limen-ai.conversations.summarizer_agent', config('limen-ai.default_agent', 'example'));

        $runId = app(AgentRuntime::class)->run(
            $agentKey,
            (string) Str::uuid(),
            "Summarize this conversation for future context:\n\n".$transcript,
            RunContextData::make(['user_id' => null]),
        );

        $run = app(RunRepository::class)->find($runId);
        $summary = trim((string) ($run['final_message'] ?? ''));

        return $summary !== '' ? $summary : null;
    }
}
