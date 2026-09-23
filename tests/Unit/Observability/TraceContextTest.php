<?php

namespace LimenAi\Tests\Unit\Observability;

use LimenAi\Observability\TraceContext;
use LimenAi\Tests\TestCase;

class TraceContextTest extends TestCase
{
    public function test_it_generates_run_and_child_spans(): void
    {
        $run = TraceContext::forRun();
        $child = TraceContext::child($run->traceId, $run->spanId);

        $this->assertNotEmpty($run->traceId);
        $this->assertNotEmpty($run->spanId);
        $this->assertSame($run->traceId, $child->traceId);
        $this->assertSame($run->spanId, $child->parentSpanId);
        $this->assertNotSame($run->spanId, $child->spanId);
    }
}
