<?php

namespace LimenAi\Contracts\Knowledge;

interface VectorStore
{
    /**
     * @param  list<array<string, mixed>>  $vectors
     */
    public function upsert(string $collection, array $vectors): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $collection, array $embedding, int $limit = 5): array;
}
