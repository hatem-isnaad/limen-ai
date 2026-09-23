<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ConfigToolDefinition;

class ConfigToolDefinitionTest extends TestCase
{
    public function test_from_config_applies_defaults(): void
    {
        $tool = ConfigToolDefinition::fromConfig('demo', [
            'description' => 'Demo tool',
        ]);

        $this->assertSame('demo', $tool->key());
        $this->assertSame('demo', $tool->name());
        $this->assertSame('Demo tool', $tool->description());
        $this->assertSame([], $tool->inputSchema());
        $this->assertSame([], $tool->authorizationConfig());
        $this->assertFalse($tool->requiresConfirmation());
        $this->assertSame(30, $tool->timeoutSeconds());
        $this->assertSame('', $tool->executorClass());
        $this->assertSame([], $tool->httpIntegration());
        $this->assertSame('1.0.0', $tool->version());
    }

    public function test_from_config_maps_all_sections(): void
    {
        $tool = ConfigToolDefinition::fromConfig('full', [
            'name' => 'Full Tool',
            'description' => 'Does work',
            'input_schema' => ['message' => ['type' => 'string']],
            'authorization' => ['roles' => ['admin']],
            'confirmation' => true,
            'timeout' => 10,
            'class' => EchoTool::class,
            'integration' => ['connector' => 'example_api'],
            'version' => '2.0.0',
        ]);

        $this->assertSame('Full Tool', $tool->name());
        $this->assertTrue($tool->requiresConfirmation());
        $this->assertSame(10, $tool->timeoutSeconds());
        $this->assertSame(EchoTool::class, $tool->executorClass());
        $this->assertSame(['connector' => 'example_api'], $tool->httpIntegration());
        $this->assertSame('2.0.0', $tool->version());
    }

    public function test_from_config_accepts_executor_alias(): void
    {
        $tool = ConfigToolDefinition::fromConfig('demo', [
            'executor' => EchoTool::class,
        ]);

        $this->assertSame(EchoTool::class, $tool->executorClass());
    }

    public function test_from_config_ignores_non_array_integration(): void
    {
        $tool = ConfigToolDefinition::fromConfig('demo', [
            'integration' => 'invalid',
        ]);

        $this->assertSame([], $tool->httpIntegration());
    }
}
