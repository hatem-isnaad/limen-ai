<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class ValidateCommandTest extends TestCase
{
    public function test_validate_command_passes_for_example_agent(): void
    {
        $this->artisan('limen-ai:validate')
            ->expectsOutputToContain('All Limen AI agent configurations are valid.')
            ->assertSuccessful();

        $this->artisan('limen-ai:validate', ['agent' => 'example'])
            ->expectsOutputToContain('Agent [example] configuration is valid.')
            ->assertSuccessful();
    }

    public function test_validate_command_fails_for_unknown_agent(): void
    {
        $this->artisan('limen-ai:validate', ['agent' => 'missing'])
            ->expectsOutputToContain('Agent [missing] was not found.')
            ->assertFailed();
    }
}
