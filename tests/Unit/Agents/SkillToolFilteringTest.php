<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Tests\TestCase;

class SkillToolFilteringTest extends TestCase
{
    public function test_it_exposes_only_skill_scoped_tools_to_the_llm(): void
    {
        config()->set('limen-ai.agents.example.tools', [
            'example_echo',
            'get_shipment_status',
            'send_customer_message',
        ]);
        config()->set('limen-ai.skills.general_assistance.tools', ['example_echo']);

        $resolved = app(AgentResolver::class)->resolve('example');

        $this->assertCount(1, $resolved->toolSchemas());
        $this->assertSame('example_echo', $resolved->toolSchemas()[0]['function']['name']);
    }
}
