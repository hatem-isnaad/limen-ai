<?php

namespace LimenAi\Tests\Unit\Security;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Security\BasicOutputModerator;
use LimenAi\Tests\TestCase;

class BasicOutputModeratorTest extends TestCase
{
    public function test_it_redacts_sensitive_patterns_from_assistant_output(): void
    {
        config()->set('limen-ai.security.output_moderation.patterns', [
            '/\bapi_key\s*[:=]\s*\S+/i',
        ]);

        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Hi',
        ]);

        $result = app(BasicOutputModerator::class)->moderate(
            $agent,
            'Your api_key=super-secret-value is unsafe.',
        );

        $this->assertStringContainsString('[redacted]', $result);
        $this->assertStringNotContainsString('super-secret-value', $result);
    }
}
