<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Memory\MemoryRetriever;
use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Memory\DefaultMemoryRetriever;
use LimenAi\Memory\InMemoryMemoryStore;
use LimenAi\Memory\MemoryService;
use LimenAi\Tests\TestCase;

class MemoryBindingTest extends TestCase
{
    public function test_memory_contracts_are_bound(): void
    {
        $this->assertInstanceOf(InMemoryMemoryStore::class, app(MemoryStore::class));
        $this->assertInstanceOf(DefaultMemoryRetriever::class, app(MemoryRetriever::class));
        $this->assertInstanceOf(MemoryService::class, app(MemoryService::class));
    }
}
