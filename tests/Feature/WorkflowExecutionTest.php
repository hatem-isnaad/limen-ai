<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Events\WorkflowCompleted;
use LimenAi\Events\WorkflowStarted;
use LimenAi\Events\WorkflowStepCompleted;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\TestCase;

class WorkflowExecutionTest extends TestCase
{
    public function test_it_executes_branching_workflow_through_tool_path(): void
    {
        Event::fake([WorkflowStarted::class, WorkflowStepCompleted::class, WorkflowCompleted::class]);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Workflow step complete.',
            'finish_reason' => 'stop',
        ]));

        $workflow = app(WorkflowRepository::class)->find('example_flow');

        $runId = app(WorkflowEngine::class)->start(
            $workflow,
            ['mode' => 'tool'],
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertArrayHasKey('echo', $run['step_outputs']);

        Event::assertDispatched(WorkflowStarted::class);
        Event::assertDispatched(WorkflowCompleted::class);
    }

    public function test_it_skips_tool_branch_when_condition_is_false(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Done.',
            'finish_reason' => 'stop',
        ]));

        $workflow = app(WorkflowRepository::class)->find('example_flow');

        $runId = app(WorkflowEngine::class)->start(
            $workflow,
            ['mode' => 'chat'],
            RunContextData::make(['user_id' => 1]),
        );

        $run = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertArrayNotHasKey('echo', $run['step_outputs']);
        $this->assertArrayHasKey('finish', $run['step_outputs']);
    }

    public function test_it_pauses_on_approval_step_and_resumes_to_completion(): void
    {
        Event::fake([ApprovalRequested::class, WorkflowCompleted::class]);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Please expect a delay.',
            'finish_reason' => 'stop',
        ]));

        $workflow = app(WorkflowRepository::class)->find('shipment_notify');
        $engine = app(WorkflowEngine::class);
        $context = RunContextData::make(['user_id' => 1]);

        $runId = $engine->start($workflow, ['shipment_id' => '12345'], $context);

        $waitingRun = app(RunRepository::class)->find($runId);
        $checkpoint = app(CheckpointStore::class)->load($runId);

        $this->assertSame(RunStatus::WAITING_APPROVAL, $waitingRun['status']);
        $this->assertNotNull($checkpoint);
        Event::assertDispatched(ApprovalRequested::class);

        $engine->resume($runId, $context);

        $completedRun = app(RunRepository::class)->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $completedRun['status']);
        $this->assertArrayHasKey('send', $completedRun['step_outputs']);
        Event::assertDispatched(WorkflowCompleted::class);
    }
}
