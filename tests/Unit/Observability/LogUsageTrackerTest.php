<?php

namespace LimenAi\Tests\Unit\Observability;

use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Observability\UsageTracker;
use LimenAi\Tests\TestCase;

class LogUsageTrackerTest extends TestCase
{
    public function test_it_records_llm_and_tool_usage_by_run(): void
    {
        $tracker = app(UsageTracker::class);
        $reader = app(UsageReader::class);

        $tracker->recordLlmUsage('run-usage', 'fake', 'gpt-test', [
            'prompt_tokens' => 30,
            'completion_tokens' => 12,
            'total_tokens' => 42,
        ]);
        $tracker->recordToolExecution('run-usage', 'example_echo', 15);

        $records = $reader->recordsForRun('run-usage');

        $this->assertCount(2, $records);
        $this->assertSame('llm', $records[0]['type']);
        $this->assertSame(30, $records[0]['input_tokens']);
        $this->assertSame(12, $records[0]['output_tokens']);
        $this->assertSame(42, $records[0]['total_tokens']);
        $this->assertSame('tool', $records[1]['type']);
        $this->assertSame('example_echo', $records[1]['tool_key']);

        $summary = $reader->summarizeForRun('run-usage');

        $this->assertSame(1, $summary['llm_calls']);
        $this->assertSame(30, $summary['input_tokens']);
        $this->assertSame(12, $summary['output_tokens']);
        $this->assertSame(30.0, $summary['avg_input_tokens']);
        $this->assertSame(12.0, $summary['avg_output_tokens']);
        $this->assertSame(1, $summary['tool_calls']);
    }

    public function test_it_calculates_global_average_usage_across_runs(): void
    {
        $tracker = app(UsageTracker::class);
        $reader = app(UsageReader::class);

        $tracker->recordLlmUsage('run-a', 'fake', 'gpt-test', [
            'prompt_tokens' => 10,
            'completion_tokens' => 5,
        ]);
        $tracker->recordLlmUsage('run-b', 'fake', 'gpt-test', [
            'prompt_tokens' => 30,
            'completion_tokens' => 15,
        ]);

        $summary = $reader->summarize();

        $this->assertSame(2, $summary['llm_calls']);
        $this->assertSame(40, $summary['input_tokens']);
        $this->assertSame(20, $summary['output_tokens']);
        $this->assertSame(20.0, $summary['avg_input_tokens']);
        $this->assertSame(10.0, $summary['avg_output_tokens']);
    }
}
