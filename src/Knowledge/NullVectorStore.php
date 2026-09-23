<?php

namespace LimenAi\Knowledge;

use LimenAi\Contracts\Knowledge\VectorStore;

class NullVectorStore implements VectorStore
{
    public function upsert(string $collection, array $vectors): void
    {
    }

    public function search(string $collection, array $embedding, int $limit = 5): array
    {
        return [];
    }
}
