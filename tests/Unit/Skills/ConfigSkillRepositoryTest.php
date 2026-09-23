<?php

namespace LimenAi\Tests\Unit\Skills;

use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Tests\TestCase;

class ConfigSkillRepositoryTest extends TestCase
{
    public function test_it_resolves_configured_skill(): void
    {
        $repository = app(SkillRepository::class);

        $skill = $repository->find('general_assistance');

        $this->assertNotNull($skill);
        $this->assertSame('general_assistance', $skill->key());
        $this->assertContains('example_echo', $skill->allowedTools());
    }

    public function test_it_returns_skills_for_agent(): void
    {
        $repository = app(SkillRepository::class);

        $skills = $repository->forAgent('example');

        $this->assertCount(1, $skills);
        $this->assertSame('general_assistance', $skills[0]->key());
    }
}
