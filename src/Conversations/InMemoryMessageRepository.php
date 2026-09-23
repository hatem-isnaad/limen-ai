<?php

namespace LimenAi\Conversations;

use Illuminate\Support\Str;
use LimenAi\Contracts\Conversations\MessageRepository;

class InMemoryMessageRepository implements MessageRepository
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $messages = [];

    public function create(string $conversationId, array $attributes): string
    {
        $messageId = (string) ($attributes['id'] ?? Str::uuid());

        $this->messages[$conversationId] ??= [];
        $this->messages[$conversationId][] = array_merge([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => '',
            'structured_content' => null,
            'metadata' => [],
            'created_at' => now()->toIso8601String(),
        ], $attributes);

        return $messageId;
    }

    public function forConversation(string $conversationId, ?int $limit = null): array
    {
        $messages = $this->messages[$conversationId] ?? [];

        if ($limit !== null) {
            return array_slice($messages, -$limit);
        }

        return $messages;
    }
}
