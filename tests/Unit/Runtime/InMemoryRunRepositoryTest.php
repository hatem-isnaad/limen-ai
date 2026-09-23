<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Exceptions\RunNotFoundException;
use LimenAi\Runtime\InMemoryRunRepository;
use LimenAi\Runtime\RunStatus;
use PHPUnit\Framework\TestCase;

class InMemoryRunRepositoryTest extends TestCase
{
    public function test_it_creates_finds_and_updates_runs(): void
    {
        $repo = new InMemoryRunRepository;

        $runId = $repo->create([
            'conversation_id' => 'conv-mem',
            'agent_key' => 'example',
            'status' => RunStatus::RUNNING,
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ]);

        $run = $repo->find($runId);

        $this->assertSame('conv-mem', $run['conversation_id']);
        $this->assertSame(RunStatus::RUNNING, $run['status']);

        $repo->updateStatus($runId, RunStatus::COMPLETED, [
            'final_message' => 'Done',
        ]);

        $updated = $repo->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $updated['status']);
        $this->assertSame('Done', $updated['final_message']);
    }

    public function test_update_status_throws_for_missing_run(): void
    {
        $this->expectException(RunNotFoundException::class);

        (new InMemoryRunRepository)->updateStatus('missing', RunStatus::FAILED);
    }

    public function test_find_returns_null_for_missing_run(): void
    {
        $this->assertNull((new InMemoryRunRepository)->find('missing'));
    }
}
