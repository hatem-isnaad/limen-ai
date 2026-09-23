<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $published = config_path('limen-ai.php');

        if (is_file($published)) {
            @unlink($published);
        }

        parent::tearDown();
    }

    public function test_it_publishes_limen_ai_assets(): void
    {
        $this->artisan('limen-ai:install')
            ->expectsOutputToContain('Limen AI installed successfully.')
            ->assertSuccessful();
    }

    public function test_published_config_includes_persistence_auto_detect(): void
    {
        $this->artisan('limen-ai:install')->assertSuccessful();

        $published = (string) file_get_contents(config_path('limen-ai.php'));

        $this->assertStringContainsString('auto_detect', $published);
        $this->assertStringContainsString('persistence', $published);
    }
}
