<?php

namespace LimenAi\Contracts\Conversations;

interface ConversationRepository
{
    public function create(array $attributes): string;

    public function find(string $conversationId): ?array;

    public function update(string $conversationId, array $attributes): void;
}
