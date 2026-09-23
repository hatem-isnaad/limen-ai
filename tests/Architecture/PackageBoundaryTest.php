<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\Architecture\Concerns\ScansPhpSources;
use LimenAi\Tests\TestCase;

class PackageBoundaryTest extends TestCase
{
    use ScansPhpSources;

    private string $src;

    protected function setUp(): void
    {
        parent::setUp();

        $this->src = dirname(__DIR__, 2).'/src';
    }

    public function test_runtime_directory_has_no_blade_or_pusher_imports(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Runtime', [
            'Illuminate\\View',
            'Pusher\\',
            'App\\Models\\',
        ]);
    }

    public function test_definition_repositories_do_not_depend_on_runtime_internals(): void
    {
        foreach (['Agents', 'Tools', 'Skills', 'Workflows', 'Knowledge'] as $module) {
            $this->assertNoForbiddenImports($this->src.'/'.$module, [
                'LimenAi\\Runtime\\AgentRuntime',
            ]);
        }
    }

    public function test_provider_layer_does_not_depend_on_blade_or_host_app(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Providers', [
            'Illuminate\\View',
            'App\\',
            'Pusher\\',
        ]);
    }

    public function test_tool_pipeline_does_not_reference_host_app_models(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Tools', [
            'App\\Models\\',
        ]);
    }

    public function test_package_does_not_reference_host_app_namespace(): void
    {
        foreach (['Agents', 'Runtime', 'Tools', 'Skills', 'Workflows', 'Memory', 'Knowledge', 'Authorization', 'Integrations', 'Providers'] as $module) {
            $this->assertNoForbiddenImports($this->src.'/'.$module, [
                'App\\',
            ]);
        }
    }
}
