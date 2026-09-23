<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\AgentValidator;
use LimenAi\Tests\TestCase;

class AgentValidatorTest extends TestCase
{
    public function test_it_validates_example_agent_successfully(): void
    {
        $errors = app(AgentValidator::class)->validate('example');

        $this->assertSame([], $errors);
    }

    public function test_it_reports_unknown_agent_errors(): void
    {
        $errors = app(AgentValidator::class)->validate('missing');

        $this->assertContains('Agent [missing] was not found.', $errors);
    }

    public function test_it_reports_missing_tool_references(): void
    {
        config()->set('limen-ai.agents.example.tools', ['missing_tool']);

        $errors = app(AgentValidator::class)->validate('example');

        $this->assertTrue(collect($errors)->contains(
            fn (string $error): bool => str_contains($error, 'missing_tool'),
        ));
    }

    public function test_it_warns_when_agent_exposes_too_many_tools(): void
    {
        config()->set('limen-ai.quality.tool_count_warn', 3);
        config()->set('limen-ai.agents.example.tools', ['a', 'b', 'c', 'd']);

        $warnings = app(AgentValidator::class)->warnings('example');

        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('tools (high)', $warnings[0]);
    }
}
