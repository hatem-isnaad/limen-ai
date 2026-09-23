<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Knowledge\InMemoryVectorStore;
use LimenAi\Providers\Fake\FakeEmbeddingProvider;
use LimenAi\Tests\TestCase;

class InMemoryVectorStoreTest extends TestCase
{
    public function test_it_searches_vectors_by_cosine_similarity(): void
    {
        $embeddings = new FakeEmbeddingProvider();
        $store = new InMemoryVectorStore();

        $store->upsert('getting_started', [[
            'id' => 'doc-1',
            'content' => 'Laravel authorization for tools.',
            'embedding' => $embeddings->embed(['Laravel authorization for tools.'])[0],
            'metadata' => [],
        ]]);

        $store->upsert('getting_started', [[
            'id' => 'doc-2',
            'content' => 'Unrelated weather forecast.',
            'embedding' => $embeddings->embed(['Unrelated weather forecast.'])[0],
            'metadata' => [],
        ]]);

        $queryEmbedding = $embeddings->embed(['Laravel tool authorization'])[0];
        $results = $store->search('getting_started', $queryEmbedding, 1);

        $this->assertCount(1, $results);
        $this->assertSame('doc-1', $results[0]['id']);
        $this->assertGreaterThan(0, $results[0]['score']);
    }

    public function test_it_upserts_vectors_by_id(): void
    {
        $embeddings = new FakeEmbeddingProvider();
        $store = new InMemoryVectorStore();

        $store->upsert('docs', [[
            'id' => 'doc-1',
            'content' => 'Original content.',
            'embedding' => $embeddings->embed(['Original content.'])[0],
            'metadata' => [],
        ]]);

        $store->upsert('docs', [[
            'id' => 'doc-1',
            'content' => 'Updated content.',
            'embedding' => $embeddings->embed(['Updated content.'])[0],
            'metadata' => ['version' => 2],
        ]]);

        $queryEmbedding = $embeddings->embed(['Updated content.'])[0];
        $results = $store->search('docs', $queryEmbedding, 1);

        $this->assertSame('Updated content.', $results[0]['content']);
        $this->assertSame(2, $results[0]['metadata']['version']);
    }
}
