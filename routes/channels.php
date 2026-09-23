<?php

use Illuminate\Support\Facades\Broadcast;
use LimenAi\Http\Services\ConversationAccessGuard;

$prefix = (string) config('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation');

Broadcast::channel($prefix.'.{conversationId}', function ($user, string $conversationId) {
    return app(ConversationAccessGuard::class)->canAccess($user, $conversationId);
});
