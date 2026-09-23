<?php

namespace LimenAi\Knowledge;

use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Contracts\Providers\EmbeddingProvider;

class KnowledgeService
{
    public function __construct(
        private readonly VectorStore $vectorStore,
        private readonly EmbeddingProvider $embeddings,
    ) {}

    public function upsert(string $collection, string $id, string $content, array $metadata = []): void
    {
        $embedding = $this->embeddings->embed([$content])[0] ?? [];

        $this->vectorStore->upsert($collection, [[
            'id' => $id,
            'content' => $content,
            'embedding' => $embedding,
            'metadata' => $metadata,
        ]]);
    }
}
