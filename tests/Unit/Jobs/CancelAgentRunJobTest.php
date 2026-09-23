<?php

namespace LimenAi\Tests\Unit\Jobs;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Jobs\CancelAgentRunJob;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use Mockery;

class CancelAgentRunJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_delegates_to_agent_runtime_cancel(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('cancel')
            ->once()
            ->with('run-cancel-1', Mockery::on(fn (RunContextData $context): bool => $context->locale() === 'fr'));

        $job = new CancelAgentRunJob('run-cancel-1', ['locale' => 'fr']);
        $job->handle($runtime);

        $this->addToAssertionCount(1);
    }
}
