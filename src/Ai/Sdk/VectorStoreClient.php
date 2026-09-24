<?php

namespace LimenAi\Ai\Sdk;

use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Knowledge\KnowledgeService;

/** SDK-style vector store facade over Limen knowledge collections. */
final class VectorStoreClient
{
    public function __construct(
        private readonly string $collection,
        private readonly KnowledgeService $knowledge,
        private readonly VectorStore $vectorStore,
        private readonly EmbeddingProvider $embeddings,
    ) {}

    public function add(string $id, string $content, array $metadata = []): void
    {
        $this->knowledge->upsert($this->collection, $id, $content, $metadata);
    }

    /** @return list<array<string, mixed>> */
    public function search(string $query, int $limit = 5): array
    {
        $embedding = $this->embeddings->embed([$query])[0] ?? [];

        return $this->vectorStore->search($this->collection, $embedding, $limit);
    }
}
