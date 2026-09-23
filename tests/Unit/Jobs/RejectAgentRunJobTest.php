<?php

namespace LimenAi\Tests\Unit\Jobs;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Jobs\RejectAgentRunJob;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use Mockery;

class RejectAgentRunJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_delegates_to_agent_runtime_reject(): void
    {
        $runtime = Mockery::mock(AgentRuntime::class);
        $runtime->shouldReceive('reject')
            ->once()
            ->with('run-reject-1', Mockery::on(fn (RunContextData $context): bool => $context->guestToken() === 'guest-abc'));

        $job = new RejectAgentRunJob('run-reject-1', ['guest_token' => 'guest-abc']);
        $job->handle($runtime);

        $this->addToAssertionCount(1);
    }
}
