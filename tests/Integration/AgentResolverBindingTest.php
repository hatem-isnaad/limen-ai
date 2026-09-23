<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Agents\DefaultAgentResolver;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Tests\TestCase;

class AgentResolverBindingTest extends TestCase
{
    public function test_agent_resolver_contract_is_bound(): void
    {
        $this->assertInstanceOf(DefaultAgentResolver::class, app(AgentResolver::class));
    }
}
