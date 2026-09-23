<?php

namespace LimenAi\Http;

use LimenAi\Http\Middleware\ThrottleAgentRequests;

final class LimenAiRouteMiddleware
{
    /**
     * @return list<class-string>
     */
    public static function forUi(): array
    {
        if (! self::shouldThrottle()) {
            return [];
        }

        return [ThrottleAgentRequests::class];
    }

    public static function shouldThrottle(): bool
    {
        if ((bool) config('limen-ai.ui.rate_limit.enabled', false)) {
            return true;
        }

        return (bool) config('limen-ai.ui.guest.enabled', false);
    }
}
