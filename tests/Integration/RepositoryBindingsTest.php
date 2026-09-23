<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Agents\ConfigAgentRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Knowledge\ConfigKnowledgeRepository;
use LimenAi\Skills\ConfigSkillRepository;
use LimenAi\Tools\CompositeToolRepository;
use LimenAi\Workflows\ConfigWorkflowRepository;
use LimenAi\Tests\TestCase;

class RepositoryBindingsTest extends TestCase
{
    public function test_repository_contracts_resolve_to_config_implementations(): void
    {
        $this->assertInstanceOf(ConfigAgentRepository::class, app(AgentRepository::class));
        $this->assertInstanceOf(CompositeToolRepository::class, app(ToolRepository::class));
        $this->assertInstanceOf(ConfigSkillRepository::class, app(SkillRepository::class));
        $this->assertInstanceOf(ConfigWorkflowRepository::class, app(WorkflowRepository::class));
        $this->assertInstanceOf(ConfigKnowledgeRepository::class, app(KnowledgeRepository::class));
    }
}
