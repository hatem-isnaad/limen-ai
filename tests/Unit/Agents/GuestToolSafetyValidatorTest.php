<?php

namespace LimenAi\Tests\Unit\Agents;

use LimenAi\Agents\AgentValidator;
use LimenAi\Tests\TestCase;

class GuestToolSafetyValidatorTest extends TestCase
{
    public function test_it_fails_when_guest_agent_exposes_non_guest_safe_tools(): void
    {
        config()->set('limen-ai.agents.example.tools', ['example_echo', 'get_shipment_status']);
        config()->set('limen-ai.agents.example.authorization.guest_allowed', true);
        config()->set('limen-ai.tools.example_echo.guest_safe', true);
        config()->set('limen-ai.tools.get_shipment_status.guest_safe', false);

        $errors = app(AgentValidator::class)->validate('example');

        $this->assertNotEmpty($errors);
        $this->assertTrue(collect($errors)->contains(
            fn (string $error): bool => str_contains($error, 'guest_safe'),
        ));
    }
}
