<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\SyncAgentRunDispatcher;
use LimenAi\Tests\TestCase;
use Mockery;

class SyncAgentRunDispatcherTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dispatch_run_executes_runtime_synchronously(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('run')
            ->once()
            ->with('example', 'conv-1', 'Hello', Mockery::type(RunContextData::class))
            ->andReturn('run-sync-1');

        $context = RunContextData::make(['user_id' => 1]);
        $result = (new SyncAgentRunDispatcher($runtime))->dispatchRun('example', 'conv-1', 'Hello', $context);

        $this->assertFalse($result->queued);
        $this->assertSame('run-sync-1', $result->runId);
    }

    public function test_dispatch_resume_executes_runtime_synchronously(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('resume')
            ->once()
            ->with('run-1', Mockery::type(RunContextData::class));

        $result = (new SyncAgentRunDispatcher($runtime))->dispatchResume('run-1', RunContextData::make());

        $this->assertFalse($result->queued);
        $this->assertSame('run-1', $result->runId);
    }

    public function test_dispatch_cancel_executes_runtime_synchronously(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('cancel')
            ->once()
            ->with('run-1', Mockery::type(RunContextData::class));

        $result = (new SyncAgentRunDispatcher($runtime))->dispatchCancel('run-1', RunContextData::make());

        $this->assertFalse($result->queued);
        $this->assertSame('run-1', $result->runId);
    }

    public function test_dispatch_reject_executes_runtime_synchronously(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('reject')
            ->once()
            ->with('run-1', Mockery::type(RunContextData::class));

        $result = (new SyncAgentRunDispatcher($runtime))->dispatchReject('run-1', RunContextData::make());

        $this->assertFalse($result->queued);
        $this->assertSame('run-1', $result->runId);
    }
}
