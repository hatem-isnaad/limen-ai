<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\RunStatusReader;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\TestCase;

class DefaultRunStatusReaderTest extends TestCase
{
    public function test_it_reports_terminal_and_non_terminal_runs(): void
    {
        $repository = app(RunRepository::class);
        $reader = app(RunStatusReader::class);

        $runId = $repository->create([
            'agent_key' => 'example',
            'conversation_id' => 'conv-status',
            'user_id' => 1,
            'status' => RunStatus::RUNNING,
            'messages' => [],
        ]);

        $this->assertFalse($reader->isTerminal($runId));
        $this->assertSame(RunStatus::RUNNING, $reader->find($runId)['status'] ?? null);

        $repository->updateStatus($runId, RunStatus::COMPLETED, ['final_message' => 'ok']);

        $this->assertTrue($reader->isTerminal($runId));
    }

    public function test_missing_run_is_terminal(): void
    {
        $this->assertTrue(app(RunStatusReader::class)->isTerminal('missing-run'));
        $this->assertNull(app(RunStatusReader::class)->find('missing-run'));
    }
}
