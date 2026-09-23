<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Exceptions\RunNotFoundException;
use LimenAi\Runtime\DatabaseRunRepository;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseRunRepositoryTest extends DatabaseTestCase
{
    public function test_it_creates_and_finds_runs(): void
    {
        $repo = app(DatabaseRunRepository::class);

        $runId = $repo->create([
            'conversation_id' => 'conv-db-1',
            'agent_key' => 'example',
            'user_id' => 1,
            'status' => RunStatus::RUNNING,
            'messages' => [['role' => 'user', 'content' => 'Hi']],
            'metadata' => ['trace_id' => 't-1'],
        ]);

        $run = $repo->find($runId);

        $this->assertNotNull($run);
        $this->assertSame('conv-db-1', $run['conversation_id']);
        $this->assertSame('example', $run['agent_key']);
        $this->assertSame(RunStatus::RUNNING, $run['status']);
        $this->assertSame('Hi', $run['messages'][0]['content']);
        $this->assertSame('t-1', $run['metadata']['trace_id']);
    }

    public function test_it_returns_null_for_missing_run(): void
    {
        $this->assertNull(app(DatabaseRunRepository::class)->find('missing-run'));
    }

    public function test_it_updates_status_and_metadata(): void
    {
        $repo = app(DatabaseRunRepository::class);

        $runId = $repo->create([
            'conversation_id' => 'conv-db-2',
            'agent_key' => 'example',
            'status' => RunStatus::RUNNING,
        ]);

        $repo->updateStatus($runId, RunStatus::COMPLETED, [
            'final_message' => 'All done.',
            'tool_call_count' => 2,
        ]);

        $run = $repo->find($runId);

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertSame('All done.', $run['final_message']);
        $this->assertSame(2, $run['tool_call_count']);
    }

    public function test_update_status_throws_for_missing_run(): void
    {
        $this->expectException(RunNotFoundException::class);

        app(DatabaseRunRepository::class)->updateStatus('missing', RunStatus::FAILED);
    }

    public function test_it_accepts_custom_run_id(): void
    {
        $repo = app(DatabaseRunRepository::class);

        $runId = $repo->create([
            'id' => 'custom-run-id',
            'conversation_id' => 'conv-db-3',
            'agent_key' => 'example',
            'status' => RunStatus::PENDING,
        ]);

        $this->assertSame('custom-run-id', $runId);
        $this->assertNotNull($repo->find('custom-run-id'));
    }
}
