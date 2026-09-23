<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Runtime\AgentRunDispatchResult;
use PHPUnit\Framework\TestCase;

class AgentRunDispatchResultTest extends TestCase
{
    public function test_it_exposes_queued_flag_and_run_id(): void
    {
        $result = new AgentRunDispatchResult(queued: true, runId: 'run-1');

        $this->assertTrue($result->queued);
        $this->assertSame('run-1', $result->runId);
    }

    public function test_run_id_defaults_to_null(): void
    {
        $result = new AgentRunDispatchResult(queued: false);

        $this->assertFalse($result->queued);
        $this->assertNull($result->runId);
    }
}
