<?php

namespace LimenAi\Tests\Unit\Memory;

use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Memory\MemoryScope;
use LimenAi\Tests\TestCase;

class InMemoryMemoryStoreTest extends TestCase
{
    public function test_it_stores_and_retrieves_scoped_memory(): void
    {
        $store = app(MemoryStore::class);

        $store->put(MemoryScope::USER, 'preferred_language', 'Arabic', [
            'scope_id' => '1',
            'agent_key' => 'example',
        ]);

        $this->assertSame('Arabic', $store->get(MemoryScope::USER, 'preferred_language', [
            'scope_id' => '1',
            'agent_key' => 'example',
        ]));

        $entries = $store->all(MemoryScope::USER, [
            'scope_id' => '1',
            'agent_key' => 'example',
        ]);

        $this->assertCount(1, $entries);
        $this->assertSame('preferred_language', $entries[0]['key']);
    }

    public function test_it_forgets_scoped_memory(): void
    {
        $store = app(MemoryStore::class);

        $store->put(MemoryScope::CONVERSATION, 'topic', 'billing', [
            'scope_id' => 'conv-1',
            'agent_key' => 'example',
        ]);

        $store->forget(MemoryScope::CONVERSATION, 'topic', [
            'scope_id' => 'conv-1',
            'agent_key' => 'example',
        ]);

        $this->assertNull($store->get(MemoryScope::CONVERSATION, 'topic', [
            'scope_id' => 'conv-1',
            'agent_key' => 'example',
        ]));
    }
}
