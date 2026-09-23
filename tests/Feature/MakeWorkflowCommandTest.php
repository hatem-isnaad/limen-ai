<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class MakeWorkflowCommandTest extends TestCase
{
    public function test_it_generates_a_workflow_config_stub(): void
    {
        $path = storage_path('framework/testing/workflows');
        config()->set('limen-ai.paths.workflows', $path);

        if (is_dir($path)) {
            array_map('unlink', glob($path.'/*.php') ?: []);
        }

        $this->artisan('limen-ai:make:workflow', [
            'name' => 'onboarding_flow',
            '--agent' => 'example',
        ])->assertSuccessful();

        $file = $path.'/onboarding_flow.php';

        $this->assertFileExists($file);
        $this->assertStringContainsString("'key' => 'onboarding_flow'", file_get_contents($file));
        $this->assertStringContainsString("'agent' => 'example'", file_get_contents($file));
    }
}
