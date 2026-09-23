<?php

namespace LimenAi\Contracts\Memory;

interface MemoryRetriever
{
    /**
     * @return list<array<string, mixed>>
     */
    public function retrieve(string $agentKey, array $context): array;
}
