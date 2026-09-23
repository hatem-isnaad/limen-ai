<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Broadcasting\AgentEventBroadcaster;
use LimenAi\Broadcasting\NullBroadcaster;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\RunStatusReader;
use LimenAi\Runtime\DefaultRunStatusReader;
use LimenAi\Runtime\QueuedAgentRunDispatcher;
use LimenAi\Runtime\SyncAgentRunDispatcher;
use LimenAi\Tests\TestCase;

class QueueBroadcastBindingTest extends TestCase
{
    public function test_queue_and_broadcasting_contracts_are_bound(): void
    {
        $this->assertInstanceOf(SyncAgentRunDispatcher::class, app(AgentRunDispatcher::class));
        $this->assertInstanceOf(DefaultRunStatusReader::class, app(RunStatusReader::class));
        $this->assertInstanceOf(NullBroadcaster::class, app(RealtimeBroadcaster::class));
        $this->assertInstanceOf(AgentEventBroadcaster::class, app(AgentEventBroadcaster::class));
    }

    public function test_queued_dispatcher_is_bound_when_configured(): void
    {
        config()->set('limen-ai.queue.agent_runs', true);
        $this->app->forgetInstance(AgentRunDispatcher::class);

        $this->assertInstanceOf(
            QueuedAgentRunDispatcher::class,
            $this->app->make(AgentRunDispatcher::class),
        );
    }
}
