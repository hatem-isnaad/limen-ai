<?php

namespace LimenAi\Tests\Unit\Runtime;

use Illuminate\Contracts\Bus\Dispatcher;
use LimenAi\Jobs\CancelAgentRunJob;
use LimenAi\Jobs\RejectAgentRunJob;
use LimenAi\Jobs\ResumeAgentRunJob;
use LimenAi\Jobs\RunAgentJob;
use LimenAi\Runtime\QueuedAgentRunDispatcher;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use Mockery;

class QueuedAgentRunDispatcherTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dispatch_run_queues_run_agent_job(): void
    {
        config()->set('limen-ai.queue.connection', 'redis');
        config()->set('limen-ai.queue.name', 'agents');

        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function (RunAgentJob $job): bool {
                return $job->agentKey === 'example'
                    && $job->conversationId === 'conv-q'
                    && $job->userMessage === 'Queue me'
                    && $job->contextAttributes['user_id'] === 3;
            }));

        $result = (new QueuedAgentRunDispatcher($bus, config()))
            ->dispatchRun('example', 'conv-q', 'Queue me', RunContextData::make(['user_id' => 3]));

        $this->assertTrue($result->queued);
        $this->assertNull($result->runId);
    }

    public function test_dispatch_resume_queues_resume_job(): void
    {
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ResumeAgentRunJob::class));

        $result = (new QueuedAgentRunDispatcher($bus, config()))
            ->dispatchResume('run-2', RunContextData::make(['locale' => 'ar']));

        $this->assertTrue($result->queued);
        $this->assertSame('run-2', $result->runId);
    }

    public function test_dispatch_cancel_queues_cancel_job(): void
    {
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(CancelAgentRunJob::class));

        $result = (new QueuedAgentRunDispatcher($bus, config()))
            ->dispatchCancel('run-3', RunContextData::make());

        $this->assertTrue($result->queued);
        $this->assertSame('run-3', $result->runId);
    }

    public function test_dispatch_reject_queues_reject_job(): void
    {
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(RejectAgentRunJob::class));

        $result = (new QueuedAgentRunDispatcher($bus, config()))
            ->dispatchReject('run-4', RunContextData::make());

        $this->assertTrue($result->queued);
        $this->assertSame('run-4', $result->runId);
    }
}
