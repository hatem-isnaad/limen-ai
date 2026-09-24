<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\ConfigAgentDefinition;
use LimenAi\Agents\InstructionComposer;
use LimenAi\Skills\ConfigSkillDefinition;
use LimenAi\Tests\TestCase;

class InstructionComposerTest extends TestCase
{
    public function test_it_composes_agent_and_skill_instructions(): void
    {
        $agent = ConfigAgentDefinition::fromConfig('example', [
            'name' => 'Example',
            'model' => 'gpt-4.1-mini',
            'provider' => 'fake',
            'instructions' => 'Base agent instructions.',
        ]);

        $skill = ConfigSkillDefinition::fromConfig('general_assistance', [
            'name' => 'General Assistance',
            'instructions' => 'Be concise and helpful.',
        ]);

        $composed = (new InstructionComposer())->compose($agent, [$skill]);

        $this->assertStringContainsString('Base agent instructions.', $composed);
        $this->assertStringContainsString('## Skill: General Assistance', $composed);
        $this->assertStringContainsString('Be concise and helpful.', $composed);
    }
}
