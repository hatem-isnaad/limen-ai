<?php

namespace LimenAi\Tests\Unit\Runtime;

use LimenAi\Runtime\ArrayCheckpointStore;
use PHPUnit\Framework\TestCase;

class ArrayCheckpointStoreTest extends TestCase
{
    public function test_it_saves_loads_and_deletes_checkpoints_in_memory(): void
    {
        $store = new ArrayCheckpointStore();

        $store->save('run-1', 2, ['messages' => []]);
        $checkpoint = $store->load('run-1');

        $this->assertSame(2, $checkpoint['step']);
        $this->assertSame(['messages' => []], $checkpoint['state']);

        $store->delete('run-1');

        $this->assertNull($store->load('run-1'));
    }

    public function test_it_overwrites_existing_checkpoint(): void
    {
        $store = new ArrayCheckpointStore();

        $store->save('run-2', 1, ['step' => 'first']);
        $store->save('run-2', 2, ['step' => 'second']);

        $checkpoint = $store->load('run-2');

        $this->assertSame(2, $checkpoint['step']);
        $this->assertSame('second', $checkpoint['state']['step']);
    }

    public function test_load_returns_null_for_missing_run(): void
    {
        $this->assertNull((new ArrayCheckpointStore())->load('missing'));
    }
}
