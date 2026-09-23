<?php

namespace LimenAi\Tests\Unit\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster as LaravelBroadcaster;
use LimenAi\Broadcasting\PusherBroadcaster;
use LimenAi\Tests\TestCase;
use Mockery;

class PusherBroadcasterTest extends TestCase
{
    public function test_it_delegates_to_laravel_broadcaster(): void
    {
        config()->set('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation');

        $laravelBroadcaster = Mockery::mock(LaravelBroadcaster::class);
        $laravelBroadcaster->shouldReceive('broadcast')
            ->once()
            ->with(
                ['limen-ai.conversation.conv-1'],
                'AgentStarted',
                ['run_id' => 'run-1'],
            );

        $manager = Mockery::mock(BroadcastManager::class);
        $manager->shouldReceive('connection')->once()->andReturn($laravelBroadcaster);

        $broadcaster = new PusherBroadcaster($manager, app('config'));

        $this->assertSame('limen-ai.conversation.conv-1', $broadcaster->conversationChannel('conv-1'));

        $broadcaster->broadcast('limen-ai.conversation.conv-1', 'AgentStarted', ['run_id' => 'run-1']);
    }
}
