<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Tests\TestCase;

class MakeToolCommandTest extends TestCase
{
    public function test_it_generates_a_tool_class_from_stub(): void
    {
        $path = storage_path('framework/testing/tools');
        config()->set('limen-ai.paths.tools', $path);

        if (is_dir($path)) {
            array_map('unlink', glob($path.'/*.php') ?: []);
        }

        $this->artisan('limen-ai:make:tool', [
            'name' => 'FetchStatus',
            '--key' => 'fetch_status',
        ])->assertSuccessful();

        $file = $path.'/FetchStatusTool.php';

        $this->assertFileExists($file);
        $this->assertStringContainsString('class FetchStatusTool extends BaseTool', file_get_contents($file));
        $this->assertStringContainsString('function authorize(', file_get_contents($file));
        $this->assertStringContainsString("return 'fetch_status';", file_get_contents($file));
    }
}
