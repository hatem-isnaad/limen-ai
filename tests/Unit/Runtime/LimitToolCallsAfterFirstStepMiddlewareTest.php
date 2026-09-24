<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Agents\ResolvedAgent;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Runtime\Middleware\LimitToolCallsAfterFirstStepMiddleware;
use LimenAi\Runtime\PendingAgentStep;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ToolSchemaBuilder;

class LimitToolCallsAfterFirstStepMiddlewareTest extends TestCase
{
    public function test_drops_tools_after_first_step(): void
    {
        $agent = new ResolvedAgent(
            definition: ConfigAgentDefinition::fromConfig('example', [
                'name' => 'Example',
                'provider' => 'fake',
                'model' => 'fake-model',
            ]),
            provider: new FakeLlmProvider,
            instructions: '',
            tools: [],
            skills: [],
            limits: [],
            toolSchemaBuilder: app(ToolSchemaBuilder::class),
        );

        $middleware = new LimitToolCallsAfterFirstStepMiddleware;
        $step = new PendingAgentStep($agent, 'run', [], [], 2);

        $processed = $middleware->handle($step, fn (PendingAgentStep $pending): PendingAgentStep => $pending);

        $this->assertTrue($processed->options['omit_tools'] ?? false);
    }

    public function test_keeps_tools_on_first_step(): void
    {
        $agent = new ResolvedAgent(
            definition: ConfigAgentDefinition::fromConfig('example', [
                'name' => 'Example',
                'provider' => 'fake',
                'model' => 'fake-model',
            ]),
            provider: new FakeLlmProvider,
            instructions: '',
            tools: [],
            skills: [],
            limits: [],
            toolSchemaBuilder: app(ToolSchemaBuilder::class),
        );

        $middleware = new LimitToolCallsAfterFirstStepMiddleware;
        $step = new PendingAgentStep($agent, 'run', [], [], 1);

        $processed = $middleware->handle($step, fn (PendingAgentStep $pending): PendingAgentStep => $pending);

        $this->assertFalse($processed->options['omit_tools'] ?? false);
    }
}
