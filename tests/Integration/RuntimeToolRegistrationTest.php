<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Support\LimenAiManager;
use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tests\TestCase;

class RuntimeToolRegistrationTest extends TestCase
{
    public function test_it_registers_runtime_tools_via_manager(): void
    {
        app(LimenAiManager::class)->registerTool('runtime_echo', EchoTool::class, [
            'name' => 'Runtime Echo',
            'description' => 'Runtime registered echo tool.',
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
            'confirmation' => false,
            'guest_safe' => true,
            'timeout' => 5,
            'version' => '1.0.0',
        ]);

        $tool = app(ToolRepository::class)->find('runtime_echo');

        $this->assertNotNull($tool);
        $this->assertSame('runtime_echo', $tool->key());
    }
}
