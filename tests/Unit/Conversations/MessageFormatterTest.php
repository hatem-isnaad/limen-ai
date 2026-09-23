<?php

namespace LimenAi\Tests\Unit\Conversations;

use LimenAi\Conversations\MessageFormatter;
use LimenAi\Tests\TestCase;

class MessageFormatterTest extends TestCase
{
    public function test_it_round_trips_agent_messages_with_tool_calls(): void
    {
        $formatter = new MessageFormatter;

        $agentMessage = [
            'role' => 'assistant',
            'content' => '',
            'tool_calls' => [[
                'id' => 'call_1',
                'function' => ['name' => 'example_echo', 'arguments' => '{}'],
            ]],
        ];

        $stored = $formatter->fromAgentMessage($agentMessage);
        $restored = $formatter->toAgentMessages([$stored]);

        $this->assertSame('assistant', $restored[0]['role']);
        $this->assertArrayHasKey('tool_calls', $restored[0]);
    }

    public function test_it_skips_empty_messages_without_structured_content(): void
    {
        $formatter = new MessageFormatter;

        $messages = $formatter->toAgentMessages([
            ['role' => 'assistant', 'content' => ''],
        ]);

        $this->assertSame([], $messages);
    }
}
