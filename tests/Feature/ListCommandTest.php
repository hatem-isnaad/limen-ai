<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class ListCommandTest extends TestCase
{
    public function test_it_lists_registered_components(): void
    {
        $this->artisan('limen-ai:list')
            ->expectsOutputToContain('example')
            ->expectsOutputToContain('example_echo')
            ->expectsOutputToContain('general_assistance')
            ->expectsOutputToContain('example_flow')
            ->assertSuccessful();
    }
}
