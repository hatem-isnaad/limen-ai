<?php

namespace LimenAi\Conversations;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\ConversationSummarizer;

class LlmConversationSummarizer implements ConversationSummarizer
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentResolver $agentResolver,
        private readonly MessageFormatter $formatter,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    public function summarize(string $conversationId, array $messages): ?string
    {
        $threshold = max(2, (int) $this->config->get('limen-ai.conversations.summary_threshold', 24));

        if (count($messages) < $threshold) {
            return null;
        }

        $conversation = $this->conversations->find($conversationId);

        if ($conversation === null) {
            return null;
        }

        $metadata = is_array($conversation['metadata'] ?? null) ? $conversation['metadata'] : [];
        $cachedSummary = is_string($metadata['conversation_summary'] ?? null)
            ? trim($metadata['conversation_summary'])
            : '';
        $cachedCount = (int) ($metadata['summary_message_count'] ?? 0);
        $refreshDelta = max(1, (int) $this->config->get('limen-ai.conversations.summary_refresh_messages', 8));

        if ($cachedSummary !== '' && count($messages) - $cachedCount < $refreshDelta) {
            return $cachedSummary;
        }

        $keepRecent = max(1, (int) $this->config->get('limen-ai.conversations.summary_keep_recent', 12));
        $toSummarize = array_slice($messages, 0, max(0, count($messages) - $keepRecent));

        if ($toSummarize === []) {
            return $cachedSummary !== '' ? $cachedSummary : null;
        }

        $agentKey = (string) ($conversation['agent_key'] ?? $this->config->get('limen-ai.default_agent', 'example'));
        $agent = $this->agentResolver->resolve($agentKey);
        $transcript = $this->buildTranscript($toSummarize);

        if ($transcript === '') {
            return $cachedSummary !== '' ? $cachedSummary : null;
        }

        $response = $agent->provider()->chat(
            messages: [
                [
                    'role' => 'system',
                    'content' => 'Summarize the conversation transcript for future context. '
                        .'Capture goals, decisions, entities (IDs, names), and unresolved questions. '
                        .'Use concise bullet points. Do not invent facts.',
                ],
                ['role' => 'user', 'content' => $transcript],
            ],
            tools: [],
            options: array_filter([
                'model' => $agent->model(),
                'temperature' => 0.2,
                'max_tokens' => min(768, (int) ($agent->limits()['max_tokens'] ?? 768)),
            ]),
        );

        $summary = trim((string) ($response->content() ?? ''));

        if ($summary === '') {
            return $cachedSummary !== '' ? $cachedSummary : null;
        }

        $this->conversations->update($conversationId, [
            'metadata' => array_merge($metadata, [
                'conversation_summary' => $summary,
                'summary_message_count' => count($messages),
                'summary_updated_at' => now()->toIso8601String(),
            ]),
        ]);

        return $summary;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    protected function buildTranscript(array $messages): string
    {
        $lines = [];

        foreach ($this->formatter->toAgentMessages($messages) as $message) {
            $role = strtoupper((string) ($message['role'] ?? 'user'));
            $content = trim((string) ($message['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $lines[] = "{$role}: {$content}";
        }

        return implode("\n", $lines);
    }
}
