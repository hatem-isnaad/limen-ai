<?php

namespace LimenAi\Http\Services;

use LimenAi\Contracts\Conversations\ConversationRepository;

class ConversationAccessGuard
{
    public function __construct(
        private readonly ConversationRepository $conversations,
    ) {}

    public function canAccess(mixed $user, string $conversationId): bool
    {
        $conversation = $this->conversations->find($conversationId);

        if ($conversation === null) {
            return false;
        }

        if ($user === null) {
            return ($conversation['guest_token'] ?? null) !== null;
        }

        $userId = is_object($user) && isset($user->id) ? (int) $user->id : null;

        if ($userId === null) {
            return false;
        }

        return (int) ($conversation['user_id'] ?? 0) === $userId;
    }
}
