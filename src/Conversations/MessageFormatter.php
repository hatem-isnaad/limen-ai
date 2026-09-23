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
            $formatted = [
                'role' => (string) ($message['role'] ?? 'user'),
                'content' => (string) ($message['content'] ?? ''),
            ];

            if (isset($message['structured_content']) && is_array($message['structured_content'])) {
                $formatted = array_merge($formatted, $message['structured_content']);
            }

            if (($formatted['content'] ?? '') === '' && ! isset($formatted['tool_calls'])) {
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

        return array_filter([
            'role' => (string) ($agentMessage['role'] ?? 'user'),
            'content' => isset($agentMessage['content']) ? (string) $agentMessage['content'] : '',
            'structured_content' => $structured !== [] ? $structured : null,
            'metadata' => [],
        ], fn ($value) => $value !== null);
    }
}
