<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PackageBoundaryTest extends TestCase
{
    public function test_runtime_directory_has_no_blade_or_pusher_imports(): void
    {
        $this->assertNoForbiddenImports(dirname(__DIR__, 2).'/src/Runtime', [
            'Illuminate\\View',
            'Pusher\\',
            'App\\Models\\',
        ]);
    }

    public function test_definition_repositories_do_not_depend_on_runtime_internals(): void
    {
        foreach (['Agents', 'Tools', 'Skills', 'Workflows', 'Knowledge'] as $module) {
            $this->assertNoForbiddenImports(dirname(__DIR__, 2).'/src/'.$module, [
                'LimenAi\\Runtime\\AgentRuntime',
            ]);
        }
    }

    public function test_provider_layer_does_not_depend_on_blade_or_host_app(): void
    {
        $this->assertNoForbiddenImports(dirname(__DIR__, 2).'/src/Providers', [
            'Illuminate\\View',
            'App\\',
            'Pusher\\',
        ]);
    }

    public function test_tool_pipeline_does_not_reference_host_app_models(): void
    {
        $this->assertNoForbiddenImports(dirname(__DIR__, 2).'/src/Tools', [
            'App\\Models\\',
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
