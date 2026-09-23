<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
use LimenAi\Contracts\Knowledge\KnowledgeRetriever;
use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Knowledge\ConfigKnowledgeRetriever;
use LimenAi\Knowledge\DefaultAgentKnowledgeRetriever;
use LimenAi\Knowledge\KnowledgeService;
use LimenAi\Knowledge\NullVectorStore;
use LimenAi\Tests\TestCase;

class KnowledgeBindingTest extends TestCase
{
    public function test_config_driver_binds_knowledge_contracts(): void
    {
        config()->set('limen-ai.knowledge.driver', 'config');

        $this->assertInstanceOf(ConfigKnowledgeRetriever::class, app(KnowledgeRetriever::class));
        $this->assertInstanceOf(NullVectorStore::class, app(VectorStore::class));
        $this->assertInstanceOf(DefaultAgentKnowledgeRetriever::class, app(AgentKnowledgeRetriever::class));
        $this->assertInstanceOf(KnowledgeService::class, app(KnowledgeService::class));
    }
}
