<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\AgentPersonaComposer;
use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class AgentPersonaComposerTest extends TestCase
{
    public function test_it_composes_static_persona_with_tone_language_and_rules(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Do work.',
            'description' => 'Demo agent',
            'persona' => [
                'display_name' => 'Demo Bot',
                'tone' => 'concise',
                'language' => 'en',
                'response_style' => 'bullet_points',
                'rules' => ['Never guess IDs.'],
                'forbidden' => ['Legal advice'],
            ],
            'output' => ['max_response_chars' => 500],
        ]);

        $composed = app(AgentPersonaComposer::class)->composeStatic($agent);

        $this->assertStringContainsString('Demo Bot', $composed);
        $this->assertStringContainsString('language [en]', $composed);
        $this->assertStringContainsString('Never guess IDs.', $composed);
        $this->assertStringContainsString('Legal advice', $composed);
        $this->assertStringContainsString('500 characters', $composed);
    }

    public function test_it_adds_runtime_locale_when_language_is_auto(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('demo', [
            'name' => 'Demo',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Do work.',
            'persona' => ['language' => 'auto'],
        ]);

        $addendum = app(AgentPersonaComposer::class)->composeRuntimeAddendum(
            $agent,
            RunContextData::make(['locale' => 'ar']),
        );

        $this->assertStringContainsString('[ar]', $addendum ?? '');
    }
}
