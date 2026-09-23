<?php

namespace LimenAi\Tests\Unit\Tools;

use LimenAi\Tools\ToolExecutionResult;
use PHPUnit\Framework\TestCase;

class ToolExecutionResultTest extends TestCase
{
    public function test_cached_factory_marks_result_from_cache(): void
    {
        $result = ToolExecutionResult::cached(['message' => 'hi'], 'exec-1');

        $this->assertTrue($result->fromCache());
        $this->assertSame(['message' => 'hi'], $result->output());
        $this->assertSame(0, $result->durationMs());
        $this->assertSame('exec-1', $result->executionId());
    }

    public function test_fresh_factory_marks_result_not_from_cache(): void
    {
        $result = ToolExecutionResult::fresh(['message' => 'hi'], 42, 'exec-2');

        $this->assertFalse($result->fromCache());
        $this->assertSame(['message' => 'hi'], $result->output());
        $this->assertSame(42, $result->durationMs());
        $this->assertSame('exec-2', $result->executionId());
    }
}
