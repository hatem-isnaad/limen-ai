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

        $tracker->recordLlmUsage('run-usage', 'fake', 'gpt-test', ['total_tokens' => 42]);
        $tracker->recordToolExecution('run-usage', 'example_echo', 15);

        $records = $reader->recordsForRun('run-usage');

        $this->assertCount(2, $records);
        $this->assertSame('llm', $records[0]['type']);
        $this->assertSame(42, $records[0]['usage']['total_tokens']);
        $this->assertSame('tool', $records[1]['type']);
        $this->assertSame('example_echo', $records[1]['tool_key']);
    }
}
