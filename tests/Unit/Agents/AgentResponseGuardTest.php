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

    public function test_it_returns_content_unchanged_when_under_limit(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => ['max_response_chars' => 100],
        ]);

        $content = 'Short reply.';
        $result = app(AgentResponseGuard::class)->apply($agent, $content);

        $this->assertSame($content, $result);
    }

    public function test_it_returns_content_unchanged_when_max_chars_is_zero(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => ['max_response_chars' => 0],
        ]);

        $content = str_repeat('x', 500);
        $result = app(AgentResponseGuard::class)->apply($agent, $content);

        $this->assertSame($content, $result);
    }

    public function test_it_handles_multibyte_characters_correctly(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => ['max_response_chars' => 4],
        ]);

        $result = app(AgentResponseGuard::class)->apply($agent, 'مرحبا');

        $this->assertSame('مرح…', $result);
    }

    public function test_it_handles_empty_content(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => ['max_response_chars' => 10],
        ]);

        $this->assertSame('', app(AgentResponseGuard::class)->apply($agent, ''));
    }

    public function test_it_truncates_exactly_at_boundary(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
            'output' => ['max_response_chars' => 5],
        ]);

        $result = app(AgentResponseGuard::class)->apply($agent, '12345');

        $this->assertSame('12345', $result);
    }
}
