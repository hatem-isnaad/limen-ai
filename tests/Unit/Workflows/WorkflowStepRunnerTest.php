<?php

namespace LimenAi\Tests\Unit\Workflows;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Exceptions\WorkflowNotFoundException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\ToolExecutionResult;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Workflows\WorkflowBranchEvaluator;
use LimenAi\Workflows\WorkflowStepRunner;
use LimenAi\Workflows\WorkflowStepType;
use LimenAi\Workflows\WorkflowVariableResolver;
use Mockery;

class WorkflowStepRunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_runs_agent_step_and_returns_output(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('run')
            ->once()
            ->andReturn('agent-run-1');

        $runs = Mockery::mock(RunRepository::class);
        $runs->shouldReceive('find')
            ->once()
            ->with('agent-run-1')
            ->andReturn(['final_message' => 'Agent finished.']);

        $runner = new WorkflowStepRunner(
            $runtime,
            app(ToolPipeline::class),
            app(ToolRepository::class),
            $runs,
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $result = $runner->run(
            'ask_agent',
            [
                'type' => WorkflowStepType::AGENT,
                'agent' => 'example',
                'message' => 'Hello {{ state.name }}',
                'next' => 'done',
            ],
            ['conversation_id' => 'conv-wf', 'name' => 'World'],
            RunContextData::make(['user_id' => 1]),
            'wf-run-1',
        );

        $this->assertSame('ask_agent', $result->stepKey());
        $this->assertSame('agent-run-1', $result->output()['agent_run_id']);
        $this->assertSame('Agent finished.', $result->output()['output']);
        $this->assertSame('done', $result->nextStep());
    }

    public function test_it_uses_default_message_when_agent_step_message_empty(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('run')
            ->once()
            ->with('example', Mockery::any(), 'Continue the workflow.', Mockery::any())
            ->andReturn('agent-run-2');

        $runs = Mockery::mock(RunRepository::class);
        $runs->shouldReceive('find')->andReturn(['final_message' => '']);

        $runner = new WorkflowStepRunner(
            $runtime,
            app(ToolPipeline::class),
            app(ToolRepository::class),
            $runs,
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $result = $runner->run(
            'ask_agent',
            ['type' => WorkflowStepType::AGENT, 'agent' => 'example'],
            ['workflow_key' => 'demo'],
            RunContextData::make(),
            'wf-run-2',
        );

        $this->assertSame('', $result->output()['output']);
    }

    public function test_it_runs_tool_step_via_pipeline(): void
    {
        $pipeline = Mockery::mock(ToolPipeline::class);
        $pipeline->shouldReceive('execute')
            ->once()
            ->with('example_echo', ['message' => 'hi'], Mockery::any())
            ->andReturn(ToolExecutionResult::fresh(['message' => 'hi'], 1, 'exec-1'));

        $runner = new WorkflowStepRunner(
            Mockery::mock(AgentRuntime::class),
            $pipeline,
            app(ToolRepository::class),
            Mockery::mock(RunRepository::class),
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $result = $runner->run(
            'echo',
            [
                'type' => WorkflowStepType::TOOL,
                'tool' => 'example_echo',
                'input' => ['message' => 'hi'],
            ],
            ['conversation_id' => 'conv-tool', 'workflow_key' => 'demo'],
            RunContextData::make(['user_id' => 1, 'metadata' => ['approval_granted' => true]]),
            'wf-run-3',
        );

        $this->assertSame(['message' => 'hi'], $result->output()['output']);
    }

    public function test_it_throws_for_missing_tool(): void
    {
        $runner = new WorkflowStepRunner(
            Mockery::mock(AgentRuntime::class),
            app(ToolPipeline::class),
            app(ToolRepository::class),
            Mockery::mock(RunRepository::class),
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $this->expectException(WorkflowNotFoundException::class);

        $runner->run(
            'missing_tool',
            ['type' => WorkflowStepType::TOOL, 'tool' => 'does_not_exist'],
            ['workflow_key' => 'demo'],
            RunContextData::make(),
            'wf-run-4',
        );
    }

    public function test_it_pauses_on_approval_step(): void
    {
        $runner = new WorkflowStepRunner(
            Mockery::mock(AgentRuntime::class),
            app(ToolPipeline::class),
            app(ToolRepository::class),
            Mockery::mock(RunRepository::class),
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $result = $runner->run(
            'approve',
            ['type' => WorkflowStepType::APPROVAL, 'message' => 'Confirm send?'],
            ['workflow_key' => 'demo'],
            RunContextData::make(),
            'wf-run-5',
        );

        $this->assertTrue($result->paused());
        $this->assertSame('Confirm send?', $result->output()['message']);
    }

    public function test_it_runs_branch_step(): void
    {
        $runner = new WorkflowStepRunner(
            Mockery::mock(AgentRuntime::class),
            app(ToolPipeline::class),
            app(ToolRepository::class),
            Mockery::mock(RunRepository::class),
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $result = $runner->run(
            'branch',
            [
                'type' => WorkflowStepType::BRANCH,
                'condition' => ['field' => 'flag', 'operator' => 'equals', 'value' => true],
                'then' => 'yes_path',
                'else' => 'no_path',
            ],
            ['flag' => true, 'workflow_key' => 'demo'],
            RunContextData::make(),
            'wf-run-6',
        );

        $this->assertSame('yes_path', $result->nextStep());
        $this->assertSame('yes_path', $result->output()['next']);
    }

    public function test_it_throws_for_unknown_step_type(): void
    {
        $runner = new WorkflowStepRunner(
            Mockery::mock(AgentRuntime::class),
            app(ToolPipeline::class),
            app(ToolRepository::class),
            Mockery::mock(RunRepository::class),
            app(WorkflowBranchEvaluator::class),
            app(WorkflowVariableResolver::class),
        );

        $this->expectException(WorkflowNotFoundException::class);

        $runner->run(
            'broken',
            ['type' => 'unknown'],
            ['workflow_key' => 'demo'],
            RunContextData::make(),
            'wf-run-7',
        );
    }
}
