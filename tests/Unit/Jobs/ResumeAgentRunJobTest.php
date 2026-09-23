<?php

namespace LimenAi\Tests\Unit\Jobs;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Jobs\ResumeAgentRunJob;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use Mockery;

class ResumeAgentRunJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_delegates_to_agent_runtime_resume(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('resume')
            ->once()
            ->with('run-resume-1', Mockery::on(fn (RunContextData $context): bool => $context->userId() === 9));

        $job = new ResumeAgentRunJob('run-resume-1', ['user_id' => 9]);
        $job->handle($runtime);

        $this->addToAssertionCount(1);
    }
}
