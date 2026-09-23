<?php

namespace LimenAi\Http\Concerns;

use Illuminate\Http\Request;
use LimenAi\Http\Services\ConversationAccessGuard;

trait AuthorizesConversationAccess
{
    protected function authorizeConversationAccess(Request $request, string $conversationId): void
    {
        abort_unless(
            app(ConversationAccessGuard::class)->canAccess(
                $request->user(),
                $conversationId,
                $request->header('X-Limen-Guest-Token'),
            ),
            403,
            'Conversation access denied.',
        );
    }
}
