<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class InspectionCommandsTest extends TestCase
{
    public function test_individual_list_commands_render_registered_components(): void
    {
        $this->artisan('limen-ai:agents')
            ->expectsOutputToContain('example:')
            ->assertSuccessful();

        $this->artisan('limen-ai:tools')
            ->expectsOutputToContain('example_echo:')
            ->assertSuccessful();

        $this->artisan('limen-ai:skills')
            ->assertSuccessful();

        $this->artisan('limen-ai:workflows')
            ->expectsOutputToContain('example_flow:')
            ->assertSuccessful();
    }
}
