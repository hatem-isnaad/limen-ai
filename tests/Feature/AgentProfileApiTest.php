<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class AgentProfileApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.ui.middleware', []);
        config()->set('limen-ai.agents.example.persona.gender', 'female');
        config()->set('limen-ai.agents.example.persona.region', 'eg');
    }

    public function test_it_returns_agent_profile_spec(): void
    {
        $this->getJson('/limen-ai/agents/example')
            ->assertOk()
            ->assertJsonPath('agent.key', 'example')
            ->assertJsonPath('agent.model', 'gpt-4.1-mini')
            ->assertJsonPath('agent.persona.gender', 'female')
            ->assertJsonPath('agent.persona.region', 'eg')
            ->assertJsonPath('agent.persona.region_label', 'Egypt');
    }
}
