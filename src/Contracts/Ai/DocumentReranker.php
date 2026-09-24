<?php

namespace LimenAi\Contracts\Ai;

interface DocumentReranker
{
    /**
     * @param  list<string>  $documents
     * @return list<array{index: int, document: string, score: float}>
     */
    public function rerank(string $query, array $documents, int $topK = 5): array;
}
