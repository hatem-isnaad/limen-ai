<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use LimenAi\Http\Middleware\ThrottleAgentRequests;
use LimenAi\Tests\TestCase;

class ThrottleAgentRequestsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.ui.middleware', [ThrottleAgentRequests::class]);
        $app['config']->set('limen-ai.ui.rate_limit.enabled', true);
        $app['config']->set('limen-ai.ui.rate_limit.max_attempts', 1);
        $app['config']->set('limen-ai.ui.rate_limit.decay_minutes', 1);
    }

    public function test_it_returns_429_when_rate_limit_is_exceeded(): void
    {
        RateLimiter::clear(sha1('127.0.0.1|/limen-ai/conversations'));

        $this->postJson('/limen-ai/conversations', ['agent' => 'example'])->assertCreated();
        $this->postJson('/limen-ai/conversations', ['agent' => 'example'])->assertStatus(429);
    }
}
