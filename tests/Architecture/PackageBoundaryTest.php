<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PackageBoundaryTest extends TestCase
{
    public function test_runtime_directory_does_not_exist_yet_or_has_no_blade_imports(): void
    {
        $runtimePath = dirname(__DIR__, 2).'/src/Runtime';

        if (! is_dir($runtimePath)) {
            $this->assertTrue(true);

            return;
        }

        $this->assertNoForbiddenImports($runtimePath, [
            'Illuminate\\View',
            'Pusher\\',
        ]);
    }

    public function test_package_does_not_reference_host_app_namespace(): void
    {
        $this->assertNoForbiddenImports(dirname(__DIR__, 2).'/src', [
            'App\\',
        ]);
    }

    /**
     * @param  list<string>  $forbidden
     */
    private function assertNoForbiddenImports(string $path, array $forbidden): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $contents,
                    sprintf('Forbidden import [%s] found in %s', $needle, $file->getPathname())
                );
            }
        }
    }
}
