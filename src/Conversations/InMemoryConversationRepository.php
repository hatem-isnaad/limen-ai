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

    public function listForUser(int $userId, ?string $agentKey = null, int $limit = 50): array
    {
        return $this->sortedList(
            array_values(array_filter(
                $this->conversations,
                static fn (array $conversation): bool => (int) ($conversation['user_id'] ?? 0) === $userId
                    && ($agentKey === null || (string) ($conversation['agent_key'] ?? '') === $agentKey),
            )),
            $limit,
        );
    }

    public function listForGuest(string $guestToken, ?string $agentKey = null, int $limit = 50): array
    {
        return $this->sortedList(
            array_values(array_filter(
                $this->conversations,
                static fn (array $conversation): bool => ($conversation['guest_token'] ?? null) === $guestToken
                    && ($agentKey === null || (string) ($conversation['agent_key'] ?? '') === $agentKey),
            )),
            $limit,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $conversations
     * @return list<array<string, mixed>>
     */
    protected function sortedList(array $conversations, int $limit): array
    {
        usort($conversations, static function (array $left, array $right): int {
            return strcmp((string) ($right['updated_at'] ?? ''), (string) ($left['updated_at'] ?? ''));
        });

        return array_slice($conversations, 0, $limit);
    }
}
