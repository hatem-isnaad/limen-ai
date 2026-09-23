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

    public function test_doctor_warns_when_in_memory_conversation_repositories_are_used(): void
    {
        $this->artisan('limen-ai:doctor')
            ->expectsOutputToContain('in-memory repositories')
            ->assertSuccessful();
    }

    public function test_doctor_fails_on_in_memory_repositories_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('limen-ai:doctor')
            ->expectsOutputToContain('in-memory repositories')
            ->assertFailed();
    }
}
