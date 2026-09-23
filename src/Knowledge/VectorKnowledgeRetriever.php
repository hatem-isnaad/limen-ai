<?php

namespace LimenAi\Knowledge;

use LimenAi\Contracts\Knowledge\KnowledgeRetriever;
use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Contracts\Providers\EmbeddingProvider;

class VectorKnowledgeRetriever implements KnowledgeRetriever
{
    public function __construct(
        private readonly VectorStore $vectorStore,
        private readonly EmbeddingProvider $embeddings,
    ) {}

    public function retrieve(string $query, array $collections, int $limit = 5): array
    {
        if ($query === '' || $collections === []) {
            return [];
        }

        $embedding = $this->embeddings->embed([$query])[0] ?? [];
        $results = [];

        foreach ($collections as $collection) {
            $collectionKey = (string) ($collection['key'] ?? '');

            if ($collectionKey === '') {
                continue;
            }

            foreach ($this->vectorStore->search($collectionKey, $embedding, $limit) as $match) {
                $results[] = [
                    'collection' => $collectionKey,
                    'content' => (string) ($match['content'] ?? ''),
                    'score' => (float) ($match['score'] ?? 0),
                    'metadata' => $match['metadata'] ?? [],
                ];
            }
        }

        usort($results, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_slice($results, 0, $limit);
    }
}
