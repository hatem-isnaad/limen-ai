<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class ResolvedAgentTest extends TestCase
{
    public function test_it_calls_provider_with_system_message_tools_and_model(): void
    {
        $fake = app(FakeLlmProvider::class);
        $fake->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'All good.',
        ]));

        $resolved = app(AgentResolver::class)->resolve('example');
        $response = $resolved->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertSame('All good.', $response->content());

        $call = $fake->recordedCalls()[0];
        $this->assertSame('system', $call['messages'][0]['role']);
        $this->assertSame('example_echo', $call['tools'][0]['function']['name']);
        $this->assertSame('gpt-4.1-mini', $call['options']['model']);
    }
}
