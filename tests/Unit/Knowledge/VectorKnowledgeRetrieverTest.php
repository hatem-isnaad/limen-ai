<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Knowledge\VectorKnowledgeRetriever;
use LimenAi\Tests\TestCase;
use Mockery;

class VectorKnowledgeRetrieverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_empty_for_empty_query(): void
    {
        $retriever = new VectorKnowledgeRetriever(
            Mockery::mock(VectorStore::class),
            Mockery::mock(EmbeddingProvider::class),
        );

        $this->assertSame([], $retriever->retrieve('', [['key' => 'docs']]));
    }

    public function test_it_returns_empty_for_empty_collections(): void
    {
        $retriever = new VectorKnowledgeRetriever(
            Mockery::mock(VectorStore::class),
            Mockery::mock(EmbeddingProvider::class),
        );

        $this->assertSame([], $retriever->retrieve('query', []));
    }

    public function test_it_searches_collections_and_sorts_by_score(): void
    {
        $embeddings = Mockery::mock(EmbeddingProvider::class);
        $embeddings->shouldReceive('embed')
            ->once()
            ->with(['billing help'])
            ->andReturn([[0.1, 0.2, 0.3]]);

        $store = Mockery::mock(VectorStore::class);
        $store->shouldReceive('search')
            ->once()
            ->with('docs', [0.1, 0.2, 0.3], 2)
            ->andReturn([
                ['content' => 'Low score', 'score' => 0.2, 'metadata' => []],
                ['content' => 'High score', 'score' => 0.9, 'metadata' => ['source' => 'faq']],
            ]);

        $retriever = new VectorKnowledgeRetriever($store, $embeddings);

        $results = $retriever->retrieve('billing help', [['key' => 'docs']], 2);

        $this->assertCount(2, $results);
        $this->assertSame('High score', $results[0]['content']);
        $this->assertSame(0.9, $results[0]['score']);
        $this->assertSame('docs', $results[0]['collection']);
        $this->assertSame(['source' => 'faq'], $results[0]['metadata']);
    }

    public function test_it_skips_collections_without_key(): void
    {
        $embeddings = Mockery::mock(EmbeddingProvider::class);
        $embeddings->shouldReceive('embed')->andReturn([[0.1]]);

        $store = Mockery::mock(VectorStore::class);
        $store->shouldNotReceive('search');

        $retriever = new VectorKnowledgeRetriever($store, $embeddings);

        $this->assertSame([], $retriever->retrieve('query', [['documents' => []]]));
    }

    public function test_it_limits_total_results_across_collections(): void
    {
        $embeddings = Mockery::mock(EmbeddingProvider::class);
        $embeddings->shouldReceive('embed')->andReturn([[0.1]]);

        $store = Mockery::mock(VectorStore::class);
        $store->shouldReceive('search')
            ->with('a', [0.1], 1)
            ->andReturn([['content' => 'A1', 'score' => 0.5]]);
        $store->shouldReceive('search')
            ->with('b', [0.1], 1)
            ->andReturn([['content' => 'B1', 'score' => 0.8]]);

        $retriever = new VectorKnowledgeRetriever($store, $embeddings);

        $results = $retriever->retrieve('query', [
            ['key' => 'a'],
            ['key' => 'b'],
        ], 1);

        $this->assertCount(1, $results);
        $this->assertSame('B1', $results[0]['content']);
    }
}
