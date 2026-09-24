<?php

namespace LimenAi\Conversations;

class MessageFormatter
{
    /**
     * @param  list<array<string, mixed>>  $storedMessages
     * @return list<array<string, mixed>>
     */
    public function toAgentMessages(array $storedMessages): array
    {
        $messages = [];

        foreach ($storedMessages as $message) {
            $content = $message['content'] ?? '';

            if (is_string($content) && str_starts_with(trim($content), '[')) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $content = $decoded;
                }
            }

            $formatted = [
                'role' => (string) ($message['role'] ?? 'user'),
                'content' => is_array($content) ? $content : (string) $content,
            ];

            if (isset($message['structured_content']) && is_array($message['structured_content'])) {
                $formatted = array_merge($formatted, $message['structured_content']);
            }

            $emptyContent = is_array($formatted['content'] ?? null)
                ? ($formatted['content'] === [])
                : (($formatted['content'] ?? '') === '');

            if ($emptyContent && ! isset($formatted['tool_calls'])) {
                continue;
            }

            $messages[] = $formatted;
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $agentMessage
     * @return array<string, mixed>
     */
    public function fromAgentMessage(array $agentMessage): array
    {
        $structured = array_diff_key($agentMessage, array_flip(['role', 'content']));

        $metadata = is_array($agentMessage['metadata'] ?? null) ? $agentMessage['metadata'] : [];

        return array_filter([
            'role' => (string) ($agentMessage['role'] ?? 'user'),
            'content' => isset($agentMessage['content'])
                ? (is_array($agentMessage['content']) ? json_encode($agentMessage['content'], JSON_THROW_ON_ERROR) : (string) $agentMessage['content'])
                : '',
            'structured_content' => $structured !== [] ? $structured : null,
            'metadata' => $metadata,
        ], fn ($value) => $value !== null);
    }
}
