<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Runtime\DatabaseCheckpointStore;
use LimenAi\Runtime\DatabaseRunRepository;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseCheckpointStoreTest extends DatabaseTestCase
{
    public function test_it_saves_and_loads_checkpoint_state(): void
    {
        $store = app(DatabaseCheckpointStore::class);

        $this->seedRun('run-cp-1');

        $store->save('run-cp-1', 3, [
            'messages' => [['role' => 'assistant', 'content' => 'Paused']],
            'pending_approval' => ['tool_key' => 'send_customer_message'],
        ]);

        $checkpoint = $store->load('run-cp-1');

        $this->assertNotNull($checkpoint);
        $this->assertSame(3, $checkpoint['step']);
        $this->assertSame('send_customer_message', $checkpoint['state']['pending_approval']['tool_key']);
    }

    public function test_it_updates_existing_checkpoint(): void
    {
        $store = app(DatabaseCheckpointStore::class);

        $this->seedRun('run-cp-2');

        $store->save('run-cp-2', 1, ['step' => 'first']);
        $store->save('run-cp-2', 2, ['step' => 'second']);

        $checkpoint = $store->load('run-cp-2');

        $this->assertSame(2, $checkpoint['step']);
        $this->assertSame('second', $checkpoint['state']['step']);
    }

    public function test_it_returns_null_for_missing_checkpoint(): void
    {
        $this->assertNull(app(DatabaseCheckpointStore::class)->load('missing-run'));
    }

    public function test_it_deletes_checkpoint(): void
    {
        $store = app(DatabaseCheckpointStore::class);

        $this->seedRun('run-cp-3');

        $store->save('run-cp-3', 1, ['ok' => true]);
        $store->delete('run-cp-3');

        $this->assertNull($store->load('run-cp-3'));
    }

    protected function seedRun(string $runId): void
    {
        app(DatabaseRunRepository::class)->create([
            'id' => $runId,
            'conversation_id' => 'conv-'.$runId,
            'agent_key' => 'example',
            'status' => RunStatus::RUNNING,
        ]);
    }
}
