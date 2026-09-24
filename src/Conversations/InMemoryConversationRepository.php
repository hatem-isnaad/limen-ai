<?php

namespace LimenAi\Conversations;

use Illuminate\Support\Str;
use LimenAi\Contracts\Conversations\ConversationRepository;

class InMemoryConversationRepository implements ConversationRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $conversations = [];

    public function create(array $attributes): string
    {
        $conversationId = (string) ($attributes['id'] ?? Str::uuid());

        $this->conversations[$conversationId] = array_merge([
            'id' => $conversationId,
            'agent_key' => null,
            'user_id' => null,
            'guest_token' => null,
            'title' => null,
            'metadata' => [],
            'state' => ConversationState::ACTIVE,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], $attributes);

        return $conversationId;
    }

    public function find(string $conversationId): ?array
    {
        return $this->conversations[$conversationId] ?? null;
    }

    public function update(string $conversationId, array $attributes): void
    {
        if (! isset($this->conversations[$conversationId])) {
            return;
        }

        $this->conversations[$conversationId] = array_merge(
            $this->conversations[$conversationId],
            $attributes,
            ['updated_at' => now()->toIso8601String()],
        );
    }
}
