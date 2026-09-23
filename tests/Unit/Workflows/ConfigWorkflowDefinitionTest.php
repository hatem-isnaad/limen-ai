<?php

namespace LimenAi\Tests\Unit\Workflows;

use LimenAi\Workflows\ConfigWorkflowDefinition;
use LimenAi\Tests\TestCase;

class ConfigWorkflowDefinitionTest extends TestCase
{
    public function test_from_config_applies_defaults(): void
    {
        $workflow = ConfigWorkflowDefinition::fromConfig('demo', [
            'start' => 'step_one',
            'steps' => ['step_one' => ['type' => 'agent']],
        ]);

        $this->assertSame('demo', $workflow->key());
        $this->assertSame('demo', $workflow->name());
        $this->assertSame('step_one', $workflow->startStep());
        $this->assertSame(['step_one' => ['type' => 'agent']], $workflow->steps());
        $this->assertSame('1.0.0', $workflow->version());
    }

    public function test_from_config_supports_nested_definition_key(): void
    {
        $workflow = ConfigWorkflowDefinition::fromConfig('nested', [
            'name' => 'Nested Workflow',
            'version' => '2.1.0',
            'definition' => [
                'start' => 'begin',
                'steps' => ['begin' => ['type' => 'tool', 'tool' => 'example_echo']],
            ],
        ]);

        $this->assertSame('Nested Workflow', $workflow->name());
        $this->assertSame('2.1.0', $workflow->version());
        $this->assertSame('begin', $workflow->startStep());
        $this->assertArrayHasKey('begin', $workflow->steps());
    }

    public function test_from_config_strips_name_and_version_from_definition(): void
    {
        $workflow = ConfigWorkflowDefinition::fromConfig('flat', [
            'name' => 'Flat',
            'version' => '3.0.0',
            'start' => 's1',
            'steps' => [],
        ]);

        $definition = $workflow->definition();

        $this->assertArrayNotHasKey('name', $definition);
        $this->assertArrayNotHasKey('version', $definition);
        $this->assertSame('s1', $definition['start']);
    }

    public function test_steps_returns_empty_array_for_invalid_definition(): void
    {
        $workflow = ConfigWorkflowDefinition::fromConfig('bad', [
            'steps' => 'invalid',
        ]);

        $this->assertSame([], $workflow->steps());
    }

    public function test_start_step_returns_empty_string_when_missing(): void
    {
        $workflow = ConfigWorkflowDefinition::fromConfig('empty', []);

        $this->assertSame('', $workflow->startStep());
    }
}
