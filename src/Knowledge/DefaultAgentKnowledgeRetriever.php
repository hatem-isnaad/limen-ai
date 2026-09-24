<?php

namespace LimenAi\Knowledge;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRetriever;

class DefaultAgentKnowledgeRetriever implements AgentKnowledgeRetriever
{
    public function __construct(
        private readonly KnowledgeRepository $knowledge,
        private readonly KnowledgeRetriever $retriever,
        private readonly KnowledgeFormatter $formatter,
        private readonly ConfigRepository $config,
    ) {}

    public function retrieve(string $agentKey, string $query): array
    {
        if (($this->config->get('limen-ai.knowledge.driver') ?? 'null') === 'null') {
            return [];
        }

        $collections = $this->knowledge->collectionsForAgent($agentKey);

        if ($collections === []) {
            return [];
        }

        $limit = (int) $this->config->get('limen-ai.knowledge.limit', 5);
        $chunks = $this->retriever->retrieve($query, $collections, $limit);

        return $this->formatter->toAgentMessages($chunks);
    }
}
