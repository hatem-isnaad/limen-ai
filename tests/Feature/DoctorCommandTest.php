<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class DoctorCommandTest extends TestCase
{
    public function test_doctor_passes_on_default_package_setup(): void
    {
        $this->artisan('limen-ai:doctor')
            ->expectsOutputToContain('Limen AI environment looks healthy.')
            ->assertSuccessful();
    }

    public function test_doctor_fails_when_default_agent_is_missing(): void
    {
        config()->set('limen-ai.default_agent', 'missing-agent');

        $this->artisan('limen-ai:doctor')
            ->expectsOutputToContain('doctor found problems')
            ->assertFailed();
    }
}
