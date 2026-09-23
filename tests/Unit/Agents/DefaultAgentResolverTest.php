<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Exceptions\AgentNotFoundException;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Tests\TestCase;

class DefaultAgentResolverTest extends TestCase
{
    public function test_it_resolves_example_agent_with_provider_tools_and_skills(): void
    {
        $resolved = app(AgentResolver::class)->resolve('example');

        $this->assertSame('example', $resolved->key());
        $this->assertSame('gpt-4.1-mini', $resolved->model());
        $this->assertSame('fake', $resolved->providerName());
        $this->assertInstanceOf(FakeLlmProvider::class, $resolved->provider());
        $this->assertCount(1, $resolved->tools());
        $this->assertCount(1, $resolved->skills());
        $this->assertStringContainsString('helpful assistant', $resolved->instructions());
        $this->assertSame('example_echo', $resolved->toolSchemas()[0]['function']['name']);
    }

    public function test_it_merges_global_and_agent_limits(): void
    {
        $resolved = app(AgentResolver::class)->resolve('example');

        $this->assertSame(10, $resolved->limits()['max_tool_calls']);
        $this->assertSame(20, $resolved->limits()['max_steps']);
        $this->assertSame(8000, $resolved->limits()['max_tokens']);
    }

    public function test_it_throws_for_missing_agent(): void
    {
        $this->expectException(AgentNotFoundException::class);

        app(AgentResolver::class)->resolve('missing');
    }
}
