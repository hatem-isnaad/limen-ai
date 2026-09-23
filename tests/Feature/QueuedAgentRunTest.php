<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Jobs\CancelAgentRunJob;
use LimenAi\Jobs\RejectAgentRunJob;
use LimenAi\Jobs\ResumeAgentRunJob;
use LimenAi\Jobs\RunAgentJob;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class QueuedAgentRunTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.queue.agent_runs', true);
    }

    public function test_it_dispatches_run_agent_job_when_queue_mode_enabled(): void
    {
        Queue::fake();

        $result = app(AgentRunDispatcher::class)->dispatchRun(
            'example',
            'conv-queue',
            'Run later',
            RunContextData::make(['user_id' => 1]),
        );

        $this->assertTrue($result->queued);
        $this->assertNull($result->runId);

        Queue::assertPushed(RunAgentJob::class, function (RunAgentJob $job): bool {
            return $job->agentKey === 'example'
                && $job->conversationId === 'conv-queue'
                && $job->userMessage === 'Run later'
                && ($job->contextAttributes['user_id'] ?? null) === 1;
        });
    }

    public function test_it_dispatches_resume_cancel_and_reject_jobs(): void
    {
        Queue::fake();

        $dispatcher = app(AgentRunDispatcher::class);
        $context = RunContextData::make(['user_id' => 1]);

        $dispatcher->dispatchResume('run-1', $context);
        $dispatcher->dispatchCancel('run-2', $context);
        $dispatcher->dispatchReject('run-3', $context);

        Queue::assertPushed(ResumeAgentRunJob::class, fn (ResumeAgentRunJob $job): bool => $job->runId === 'run-1');
        Queue::assertPushed(CancelAgentRunJob::class, fn (CancelAgentRunJob $job): bool => $job->runId === 'run-2');
        Queue::assertPushed(RejectAgentRunJob::class, fn (RejectAgentRunJob $job): bool => $job->runId === 'run-3');
    }

    public function test_sync_dispatcher_runs_immediately_by_default(): void
    {
        config()->set('limen-ai.queue.agent_runs', false);
        $this->app->forgetInstance(AgentRunDispatcher::class);
        Queue::fake();

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Sync response.',
            'finish_reason' => 'stop',
        ]));

        $result = app(AgentRunDispatcher::class)->dispatchRun(
            'example',
            'conv-sync',
            'Run now',
            RunContextData::make(['user_id' => 1]),
        );

        $this->assertFalse($result->queued);
        $this->assertNotEmpty($result->runId);
        Queue::assertNothingPushed();
    }
}
