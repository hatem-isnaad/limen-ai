<?php

namespace LimenAi\Tests\Unit\Enableable;

use Illuminate\Auth\GenericUser;
use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Tools\ClassBasedToolExecutor;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Exceptions\AgentDisabledException;
use LimenAi\Exceptions\ToolDisabledException;
use LimenAi\Exceptions\WorkflowDisabledException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\Stubs\DisabledEchoTool;
use LimenAi\Tests\Stubs\EchoTool;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ConfigToolDefinition;
use LimenAi\Workflows\ConfigWorkflowDefinition;

class EnableableFeatureTest extends TestCase
{
    public function test_disabled_agents_are_hidden_from_repository_listings(): void
    {
        config()->set('limen-ai.agents.hidden', [
            'enabled' => false,
            'name' => 'Hidden Agent',
            'instructions' => 'Hidden',
        ]);

        $keys = array_map(
            fn ($agent) => $agent->key(),
            app(AgentRepository::class)->all(),
        );

        $this->assertNotContains('hidden', $keys);
        $this->assertContains('example', $keys);
    }

    public function test_disabled_agents_cannot_be_authorized(): void
    {
        $this->actingAs(new GenericUser(['id' => 1]));

        $agent = ConfigAgentDefinition::fromConfig('disabled_agent', [
            'enabled' => false,
            'name' => 'Disabled Agent',
            'instructions' => 'Disabled',
        ]);

        $this->expectException(AgentDisabledException::class);

        app(AuthorizationService::class)->authorizeAgent($agent);
    }

    public function test_disabled_agents_cannot_be_resolved(): void
    {
        config()->set('limen-ai.agents.disabled_example', [
            'enabled' => false,
            'name' => 'Disabled Example',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Disabled',
            'tools' => [],
            'skills' => [],
        ]);

        $this->expectException(AgentDisabledException::class);

        app(AgentResolver::class)->resolve('disabled_example');
    }

    public function test_disabled_tools_are_excluded_from_agent_tool_sets(): void
    {
        config()->set('limen-ai.tools.disabled_echo', [
            'enabled' => false,
            'name' => 'Disabled Echo',
            'class' => EchoTool::class,
            'input_schema' => ['message' => ['type' => 'string', 'required' => true]],
        ]);

        config()->set('limen-ai.agents.with_disabled_tool', [
            'name' => 'With Disabled Tool',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Test',
            'tools' => ['example_echo', 'disabled_echo'],
            'skills' => [],
        ]);

        $tools = app(ToolRepository::class)->forAgent('with_disabled_tool');

        $this->assertCount(1, $tools);
        $this->assertSame('example_echo', $tools[0]->key());
    }

    public function test_disabled_tools_throw_when_executed(): void
    {
        $tool = ConfigToolDefinition::fromConfig('disabled_echo', [
            'enabled' => false,
            'name' => 'Disabled Echo',
            'class' => EchoTool::class,
            'input_schema' => ['message' => ['type' => 'string', 'required' => true]],
        ]);

        $context = RunContextData::make(['user_id' => 1])
            ->forToolExecution('run-1', 'conv-1', 'example');

        $this->expectException(ToolDisabledException::class);

        (new ClassBasedToolExecutor(
            app('Illuminate\Contracts\Container\Container'),
            app(\LimenAi\Contracts\Integrations\HttpToolExecutor::class),
        ))->execute($tool, ['message' => 'hello'], $context);
    }

    public function test_runtime_disabled_tool_executors_are_blocked(): void
    {
        $tool = ConfigToolDefinition::fromConfig('disabled_echo', [
            'enabled' => true,
            'name' => 'Disabled Echo',
            'class' => DisabledEchoTool::class,
            'input_schema' => ['message' => ['type' => 'string', 'required' => true]],
        ]);

        $context = RunContextData::make(['user_id' => 1])
            ->forToolExecution('run-1', 'conv-1', 'example');

        $this->expectException(ToolDisabledException::class);

        (new ClassBasedToolExecutor(
            app('Illuminate\Contracts\Container\Container'),
            app(\LimenAi\Contracts\Integrations\HttpToolExecutor::class),
        ))->execute($tool, ['message' => 'hello'], $context);
    }

    public function test_disabled_workflows_cannot_be_started(): void
    {
        $workflow = ConfigWorkflowDefinition::fromConfig('disabled_flow', [
            'enabled' => false,
            'name' => 'Disabled Flow',
            'start' => 'finish',
            'steps' => [
                'finish' => ['type' => 'agent', 'agent' => 'example', 'message' => 'done'],
            ],
        ]);

        $this->expectException(WorkflowDisabledException::class);

        app(WorkflowEngine::class)->start(
            $workflow,
            [],
            RunContextData::make(['user_id' => 1]),
        );
    }

    public function test_disabled_workflows_are_hidden_from_repository_listings(): void
    {
        config()->set('limen-ai.workflows.hidden_flow', [
            'enabled' => false,
            'name' => 'Hidden Flow',
            'start' => 'finish',
            'steps' => [
                'finish' => ['type' => 'agent', 'agent' => 'example', 'message' => 'done'],
            ],
        ]);

        $keys = array_map(
            fn ($workflow) => $workflow->key(),
            app(WorkflowRepository::class)->all(),
        );

        $this->assertNotContains('hidden_flow', $keys);
    }
}
