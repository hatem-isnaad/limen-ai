<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Tests\TestCase;

class ConfigAgentDefinitionTest extends TestCase
{
    public function test_from_config_applies_defaults(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'instructions' => 'Work.',
        ]);

        $this->assertSame('demo', $agent->key());
        $this->assertSame('demo', $agent->name());
        $this->assertNull($agent->description());
        $this->assertSame('', $agent->model());
        $this->assertSame('fake', $agent->provider());
        $this->assertSame('Work.', $agent->instructions());
        $this->assertSame([], $agent->skills());
        $this->assertSame([], $agent->tools());
        $this->assertSame([], $agent->knowledge());
        $this->assertSame([], $agent->memoryConfig());
        $this->assertSame([], $agent->personaConfig());
        $this->assertSame([], $agent->authorizationConfig());
        $this->assertSame([], $agent->outputConfig());
        $this->assertSame([], $agent->limits());
        $this->assertSame('1.0.0', $agent->version());
    }

    public function test_from_config_maps_all_sections(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('full', [
            'name' => 'Full Agent',
            'description' => 'Does everything',
            'model' => 'gpt-4.1',
            'provider' => 'openai',
            'instructions' => 'Follow rules.',
            'skills' => ['skill_a'],
            'tools' => ['tool_a'],
            'knowledge' => ['docs'],
            'memory' => ['limit' => 10],
            'persona' => ['tone' => 'formal'],
            'authorization' => ['roles' => ['admin']],
            'output' => ['format' => 'markdown'],
            'limits' => ['max_tool_calls' => 5],
            'version' => '2.0.0',
        ]);

        $this->assertSame('Full Agent', $agent->name());
        $this->assertSame('Does everything', $agent->description());
        $this->assertSame('gpt-4.1', $agent->model());
        $this->assertSame('openai', $agent->provider());
        $this->assertSame(['skill_a'], $agent->skills());
        $this->assertSame(['tool_a'], $agent->tools());
        $this->assertSame(['docs'], $agent->knowledge());
        $this->assertSame(['limit' => 10], $agent->memoryConfig());
        $this->assertSame(['tone' => 'formal'], $agent->personaConfig());
        $this->assertSame(['roles' => ['admin']], $agent->authorizationConfig());
        $this->assertSame(['format' => 'markdown'], $agent->outputConfig());
        $this->assertSame(['max_tool_calls' => 5], $agent->limits());
        $this->assertSame('2.0.0', $agent->version());
    }

    public function test_from_config_reindexes_skills_tools_and_knowledge(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'skills' => ['b' => 'skill_b', 'a' => 'skill_a'],
            'tools' => ['x' => 'tool_x'],
            'knowledge' => ['k' => 'docs'],
        ]);

        $this->assertSame(['skill_b', 'skill_a'], $agent->skills());
        $this->assertSame(['tool_x'], $agent->tools());
        $this->assertSame(['docs'], $agent->knowledge());
    }

    public function test_from_config_uses_default_provider_from_config(): void
    {
        config()->set('limen-ai.providers.default', 'anthropic');

        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'model' => 'claude',
        ]);

        $this->assertSame('anthropic', $agent->provider());
    }
}
