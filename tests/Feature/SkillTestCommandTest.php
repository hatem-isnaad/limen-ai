<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class SkillTestCommandTest extends TestCase
{
    public function test_it_prints_skill_instructions(): void
    {
        $this->artisan('limen-ai:skill:test', ['skill' => 'general_assistance'])
            ->expectsOutputToContain('Provide helpful, concise responses.')
            ->expectsOutputToContain('example_echo')
            ->assertSuccessful();
    }

    public function test_it_can_preview_composed_agent_instructions(): void
    {
        $this->artisan('limen-ai:skill:test', [
            'skill' => 'general_assistance',
            '--agent' => 'example',
        ])
            ->expectsOutputToContain('Composed instructions for agent [example]')
            ->expectsOutputToContain('Skill: General Assistance')
            ->assertSuccessful();
    }
}
