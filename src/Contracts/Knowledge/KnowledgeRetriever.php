<?php

namespace LimenAi\Contracts\Knowledge;

interface KnowledgeRetriever
{
    /**
     * @return list<array<string, mixed>>
     */
    public function retrieve(string $query, array $collections, int $limit = 5): array;
}
