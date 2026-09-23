<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Exceptions\RuntimeLimitExceededException;
use LimenAi\Runtime\RuntimeLimits;
use LimenAi\Tests\TestCase;

class RuntimeLimitsTest extends TestCase
{
    public function test_it_enforces_max_steps(): void
    {
        $limits = new RuntimeLimits(['max_steps' => 2], microtime(true));

        $limits->nextStep();
        $limits->nextStep();

        $this->expectException(RuntimeLimitExceededException::class);
        $limits->nextStep();
    }

    public function test_it_enforces_max_tool_calls(): void
    {
        $limits = new RuntimeLimits(['max_tool_calls' => 1], microtime(true));

        $limits->recordToolCalls();

        $this->expectException(RuntimeLimitExceededException::class);
        $limits->recordToolCalls();
    }
}
