<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class RunContextDataTest extends TestCase
{
    public function test_it_builds_run_context_from_attributes(): void
    {
        $context = RunContextData::make([
            'user_id' => 42,
            'locale' => 'ar',
            'metadata' => ['channel' => 'web'],
        ]);

        $this->assertSame(42, $context->userId());
        $this->assertSame('ar', $context->locale());
        $this->assertSame(['channel' => 'web'], $context->metadata());
    }

    public function test_it_builds_tool_execution_context(): void
    {
        $context = RunContextData::make(['user_id' => 1])
            ->forToolExecution('run-1', 'conv-1', 'example');

        $this->assertSame('run-1', $context->runId());
        $this->assertSame('conv-1', $context->conversationId());
        $this->assertSame('example', $context->agentKey());
        $this->assertSame(1, $context->userId());
    }
}
