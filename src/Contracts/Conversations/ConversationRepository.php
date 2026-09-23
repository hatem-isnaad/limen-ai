<?php

namespace LimenAi\Contracts\Conversations;

interface ConversationRepository
{
    public function create(array $attributes): string;

    public function find(string $conversationId): ?array;

    public function update(string $conversationId, array $attributes): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, ?string $agentKey = null, int $limit = 50): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForGuest(string $guestToken, ?string $agentKey = null, int $limit = 50): array;
}
