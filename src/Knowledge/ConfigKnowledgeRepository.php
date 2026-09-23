<?php

namespace LimenAi\Knowledge;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRepository;

class ConfigKnowledgeRepository implements KnowledgeRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentRepository $agents,
    ) {}

    public function findCollection(string $key): ?array
    {
        $collection = $this->config->get("limen-ai.knowledge.collections.{$key}");

        if (! is_array($collection)) {
            return null;
        }

        return array_merge(['key' => $key], $collection);
    }

    public function collectionsForAgent(string $agentKey): array
    {
        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return [];
        }

        $collections = [];

        foreach ($agent->knowledge() as $collectionKey) {
            $collection = $this->findCollection($collectionKey);

            if ($collection !== null) {
                $collections[] = $collection;
            }
        }

        return $collections;
    }
}
