<?php

namespace LimenAi\Tests\Unit\Console;

use LimenAi\Console\StubGenerator;
use LimenAi\Tests\TestCase;

class StubGeneratorTest extends TestCase
{
    public function test_it_replaces_placeholders_when_generating_files(): void
    {
        $target = storage_path('framework/testing/stub-output/ExampleTool.php');

        if (file_exists($target)) {
            unlink($target);
        }

        $path = app(StubGenerator::class)->generate('tool.stub', [
            'namespace' => 'App\\LimenAi\\Tools',
            'class' => 'ExampleTool',
            'key' => 'example_tool',
        ], $target);

        $this->assertSame($target, $path);
        $this->assertStringContainsString('namespace App\\LimenAi\\Tools;', file_get_contents($target));
        $this->assertStringContainsString('class ExampleTool implements Tool', file_get_contents($target));
    }
}
