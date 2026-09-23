<?php

namespace LimenAi\Http\Services;

use LimenAi\Contracts\Authorization\GuestSessionValidator;
use LimenAi\Contracts\Conversations\ConversationRepository;

class ConversationAccessGuard
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly GuestSessionValidator $guestSessions,
    ) {}

    public function canAccess(mixed $user, string $conversationId, ?string $guestToken = null): bool
    {
        $conversation = $this->conversations->find($conversationId);

        if ($conversation === null) {
            return false;
        }

        if ($user !== null) {
            $userId = is_object($user) && isset($user->id) ? (int) $user->id : null;

            if ($userId === null) {
                return false;
            }

            return (int) ($conversation['user_id'] ?? 0) === $userId;
        }

        $conversationToken = (string) ($conversation['guest_token'] ?? '');

        if ($conversationToken === '' || $guestToken === null || $guestToken === '') {
            return false;
        }

        if (! hash_equals($conversationToken, $guestToken)) {
            return false;
        }

        return $this->guestSessions->isValid($guestToken);
    }
}
