<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    public function test_it_publishes_limen_ai_assets(): void
    {
        $this->artisan('limen-ai:install')
            ->expectsOutputToContain('Limen AI installed successfully.')
            ->assertSuccessful();
    }
}
