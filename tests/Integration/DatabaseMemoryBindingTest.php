<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Memory\DatabaseMemoryStore;
use LimenAi\Memory\MemoryScope;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseMemoryBindingTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.memory.store', DatabaseMemoryStore::class);
    }

    public function test_database_memory_store_persists_entries(): void
    {
        $this->assertInstanceOf(DatabaseMemoryStore::class, app(MemoryStore::class));

        $store = app(MemoryStore::class);
        $store->put(MemoryScope::USER, 'timezone', 'Asia/Riyadh', [
            'scope_id' => '42',
            'agent_key' => 'example',
        ]);

        $this->assertSame('Asia/Riyadh', $store->get(MemoryScope::USER, 'timezone', [
            'scope_id' => '42',
            'agent_key' => 'example',
        ]));
    }
}
