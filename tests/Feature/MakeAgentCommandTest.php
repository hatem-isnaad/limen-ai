<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class MakeAgentCommandTest extends TestCase
{
    public function test_it_generates_an_agent_config_stub(): void
    {
        $path = storage_path('framework/testing/agents');
        config()->set('limen-ai.paths.agents', $path);

        if (is_dir($path)) {
            array_map('unlink', glob($path.'/*.php') ?: []);
        }

        $this->artisan('limen-ai:make:agent', [
            'name' => 'support',
            '--tools' => ['example_echo'],
        ])->assertSuccessful();

        $file = $path.'/support.php';

        $this->assertFileExists($file);
        $this->assertStringContainsString("'key' => 'support'", file_get_contents($file));
        $this->assertStringContainsString('example_echo', file_get_contents($file));
    }
}
