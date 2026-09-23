<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Broadcasting\AgentEventBroadcaster;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentStarted;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\Stubs\FakeRealtimeBroadcaster;
use LimenAi\Tests\TestCase;

class AgentEventBroadcastTest extends TestCase
{
    public function test_it_broadcasts_agent_lifecycle_events_to_conversation_channel(): void
    {
        $broadcaster = new FakeRealtimeBroadcaster();
        $listener = new AgentEventBroadcaster($broadcaster);
        $context = RunContextData::make(['user_id' => 1]);

        $listener->handleAgentStarted(new AgentStarted('run-1', 'example', 'conv-broadcast', $context));
        $listener->handleAgentCompleted(new AgentCompleted(
            'run-1',
            'example',
            'conv-broadcast',
            'Done.',
            $context,
        ));

        $broadcasts = $broadcaster->broadcasts();

        $this->assertCount(2, $broadcasts);
        $this->assertSame('limen-ai.conversation.conv-broadcast', $broadcasts[0]['channel']);
        $this->assertSame('AgentStarted', $broadcasts[0]['event']);
        $this->assertSame('run-1', $broadcasts[0]['payload']['run_id']);
        $this->assertSame('AgentCompleted', $broadcasts[1]['event']);
        $this->assertSame('Done.', $broadcasts[1]['payload']['final_message']);
    }
}
