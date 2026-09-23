<?php

namespace LimenAi\Contracts\Conversations;

interface MessageRepository
{
    public function create(string $conversationId, array $attributes): string;

    /** @return list<array<string, mixed>> */
    public function forConversation(string $conversationId, ?int $limit = null): array;
}
