<?php

namespace LimenAi\Tests\Unit\Http;

use LimenAi\Http\LimenAiRouteMiddleware;
use LimenAi\Http\Middleware\ThrottleAgentRequests;
use LimenAi\Tests\TestCase;

class LimenAiRouteMiddlewareTest extends TestCase
{
    public function test_it_enables_throttle_for_guest_widgets_without_explicit_rate_limit_flag(): void
    {
        config()->set('limen-ai.ui.rate_limit.enabled', false);
        config()->set('limen-ai.ui.guest.enabled', true);

        $this->assertTrue(LimenAiRouteMiddleware::shouldThrottle());
        $this->assertSame([ThrottleAgentRequests::class], LimenAiRouteMiddleware::forUi());
    }

    public function test_it_skips_throttle_when_guest_and_rate_limit_are_disabled(): void
    {
        config()->set('limen-ai.ui.rate_limit.enabled', false);
        config()->set('limen-ai.ui.guest.enabled', false);

        $this->assertFalse(LimenAiRouteMiddleware::shouldThrottle());
        $this->assertSame([], LimenAiRouteMiddleware::forUi());
    }
}
