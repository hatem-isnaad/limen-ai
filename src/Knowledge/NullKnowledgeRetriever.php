<?php

namespace LimenAi\Knowledge;

use LimenAi\Contracts\Knowledge\KnowledgeRetriever;

class NullKnowledgeRetriever implements KnowledgeRetriever
{
    public function retrieve(string $query, array $collections, int $limit = 5): array
    {
        return [];
    }
}
