<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Tests\TestCase;

class ConfigAgentRepositoryTest extends TestCase
{
    public function test_it_resolves_example_agent_from_config(): void
    {
        $repository = app(AgentRepository::class);

        $agent = $repository->find('example');

        $this->assertNotNull($agent);
        $this->assertSame('example', $agent->key());
        $this->assertSame('Example Agent', $agent->name());
        $this->assertSame('gpt-4.1-mini', $agent->model());
        $this->assertContains('example_echo', $agent->tools());
        $this->assertContains('general_assistance', $agent->skills());
        $this->assertContains('getting_started', $agent->knowledge());
    }

    public function test_it_returns_null_for_unknown_agent(): void
    {
        $repository = app(AgentRepository::class);

        $this->assertNull($repository->find('missing'));
        $this->assertFalse($repository->exists('missing'));
    }

    public function test_it_lists_all_configured_agents(): void
    {
        $repository = app(AgentRepository::class);

        $agents = $repository->all();

        $this->assertCount(7, $agents);
        $this->assertSame(
            ['app_assistant', 'example', 'example_openai', 'example_anthropic', 'example_gemini', 'example_openrouter', 'limen_3pl'],
            array_map(fn ($agent) => $agent->key(), $agents),
        );
    }
}
