<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\AgentResponseGuard;
use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Tests\TestCase;

class AgentResponseGuardTest extends TestCase
{
    public function test_it_truncates_responses_to_configured_max_chars(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => ['max_response_chars' => 10],
        ]);

        $result = app(AgentResponseGuard::class)->apply($agent, '123456789012345');

        $this->assertSame('123456789…', $result);
    }
}
