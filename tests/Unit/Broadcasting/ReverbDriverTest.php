<?php

namespace LimenAi\Tests\Unit\Broadcasting;

use LimenAi\Broadcasting\PusherBroadcaster;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Tests\TestCase;

class ReverbDriverTest extends TestCase
{
    public function test_reverb_driver_uses_laravel_broadcast_broadcaster(): void
    {
        config()->set('limen-ai.broadcasting.driver', 'reverb');
        $this->app->forgetInstance(RealtimeBroadcaster::class);

        $this->assertInstanceOf(PusherBroadcaster::class, app(RealtimeBroadcaster::class));
    }
}
