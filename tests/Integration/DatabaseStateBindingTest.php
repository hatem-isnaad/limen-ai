<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Authorization\DatabaseApprovalRepository;
use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\ArrayCheckpointStore;
use LimenAi\Runtime\DatabaseCheckpointStore;
use LimenAi\Runtime\DatabaseRunRepository;
use LimenAi\Runtime\InMemoryRunRepository;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseStateBindingTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.runtime.run_repository', DatabaseRunRepository::class);
        $app['config']->set('limen-ai.runtime.checkpoint_store', DatabaseCheckpointStore::class);
        $app['config']->set('limen-ai.runtime.approval_repository', DatabaseApprovalRepository::class);
    }

    public function test_database_runtime_repositories_persist_runs_checkpoints_and_approvals(): void
    {
        $this->assertInstanceOf(DatabaseRunRepository::class, app(RunRepository::class));
        $this->assertInstanceOf(DatabaseCheckpointStore::class, app(CheckpointStore::class));
        $this->assertInstanceOf(DatabaseApprovalRepository::class, app(ApprovalRepository::class));

        $runId = app(RunRepository::class)->create([
            'conversation_id' => 'conv-db',
            'agent_key' => 'example',
            'user_id' => 1,
            'status' => RunStatus::RUNNING,
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ]);

        app(CheckpointStore::class)->save($runId, 2, [
            'messages' => [['role' => 'user', 'content' => 'Hi']],
            'pending_approval' => ['tool_key' => 'confirmation_tool'],
        ]);

        $approvalId = app(ApprovalRepository::class)->request($runId, 'confirmation_tool', [
            'message' => 'send',
        ], 1);

        $run = app(RunRepository::class)->find($runId);
        $checkpoint = app(CheckpointStore::class)->load($runId);
        $approval = app(ApprovalRepository::class)->findPendingForRun($runId);

        $this->assertSame(RunStatus::RUNNING, $run['status']);
        $this->assertSame(2, $checkpoint['step']);
        $this->assertSame($approvalId, $approval['id']);
    }
}
