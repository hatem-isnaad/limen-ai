<?php

namespace LimenAi\Contracts\Knowledge;

interface KnowledgeRepository
{
    public function findCollection(string $key): ?array;

    /** @return list<array<string, mixed>> */
    public function collectionsForAgent(string $agentKey): array;
}
