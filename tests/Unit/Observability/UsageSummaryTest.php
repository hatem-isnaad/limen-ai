<?php

namespace LimenAi\Tests\Unit\Observability;

use LimenAi\Observability\UsageSummary;
use LimenAi\Tests\TestCase;

class UsageSummaryTest extends TestCase
{
    public function test_it_calculates_token_totals_and_averages_for_a_run(): void
    {
        $summary = UsageSummary::fromRecords([
            [
                'type' => 'llm',
                'input_tokens' => 80,
                'output_tokens' => 48,
                'total_tokens' => 128,
            ],
            [
                'type' => 'llm',
                'input_tokens' => 20,
                'output_tokens' => 10,
                'total_tokens' => 30,
            ],
            [
                'type' => 'tool',
                'duration_ms' => 15,
            ],
            [
                'type' => 'tool',
                'duration_ms' => 25,
            ],
        ]);

        $this->assertSame(2, $summary['llm_calls']);
        $this->assertSame(100, $summary['input_tokens']);
        $this->assertSame(58, $summary['output_tokens']);
        $this->assertSame(158, $summary['total_tokens']);
        $this->assertSame(50.0, $summary['avg_input_tokens']);
        $this->assertSame(29.0, $summary['avg_output_tokens']);
        $this->assertSame(79.0, $summary['avg_total_tokens']);
        $this->assertSame(2, $summary['tool_calls']);
        $this->assertSame(40, $summary['total_tool_duration_ms']);
        $this->assertSame(20.0, $summary['avg_tool_duration_ms']);
    }

    public function test_it_returns_zero_averages_when_no_llm_calls_exist(): void
    {
        $summary = UsageSummary::fromRecords([
            ['type' => 'tool', 'duration_ms' => 10],
        ]);

        $this->assertSame(0, $summary['llm_calls']);
        $this->assertSame(0.0, $summary['avg_input_tokens']);
        $this->assertSame(0.0, $summary['avg_output_tokens']);
        $this->assertSame(1, $summary['tool_calls']);
        $this->assertSame(10.0, $summary['avg_tool_duration_ms']);
    }
}
