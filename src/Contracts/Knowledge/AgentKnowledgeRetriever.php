<?php

namespace LimenAi\Contracts\Knowledge;

interface AgentKnowledgeRetriever
{
    /**
     * @return list<array<string, mixed>>
     */
    public function retrieve(string $agentKey, string $query): array;
}
