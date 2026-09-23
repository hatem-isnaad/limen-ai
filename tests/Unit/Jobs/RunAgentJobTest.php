<?php

namespace LimenAi\Tests\Unit\Jobs;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Jobs\RunAgentJob;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use Mockery;

class RunAgentJobTest extends TestCase
{
    public function test_it_delegates_to_agent_runtime(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('run')
            ->once()
            ->with(
                'example',
                'conv-job',
                'Hello queue',
                Mockery::on(fn (RunContextData $context): bool => $context->userId() === 1),
            )
            ->andReturn('run-job-1');

        $job = new RunAgentJob('example', 'conv-job', 'Hello queue', ['user_id' => 1]);

        $this->assertSame('run-job-1', $job->handle($runtime));
    }
}
