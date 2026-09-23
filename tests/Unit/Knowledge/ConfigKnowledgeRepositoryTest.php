<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Tests\TestCase;

class ConfigKnowledgeRepositoryTest extends TestCase
{
    public function test_it_resolves_knowledge_collection(): void
    {
        $repository = app(KnowledgeRepository::class);

        $collection = $repository->findCollection('getting_started');

        $this->assertNotNull($collection);
        $this->assertSame('getting_started', $collection['key']);
        $this->assertSame('Getting Started', $collection['name']);
    }

    public function test_it_returns_collections_for_agent(): void
    {
        $repository = app(KnowledgeRepository::class);

        $collections = $repository->collectionsForAgent('example');

        $this->assertCount(1, $collections);
        $this->assertSame('getting_started', $collections[0]['key']);
    }
}
