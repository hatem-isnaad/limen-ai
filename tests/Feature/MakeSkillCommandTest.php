<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class MakeSkillCommandTest extends TestCase
{
    public function test_it_generates_a_skill_config_stub(): void
    {
        $path = storage_path('framework/testing/skills');
        config()->set('limen-ai.paths.skills', $path);

        if (is_dir($path)) {
            array_map('unlink', glob($path.'/*.php') ?: []);
        }

        $this->artisan('limen-ai:make:skill', [
            'name' => 'billing_help',
        ])->assertSuccessful();

        $file = $path.'/billing_help.php';

        $this->assertFileExists($file);
        $this->assertStringContainsString("'key' => 'billing_help'", file_get_contents($file));
    }
}
