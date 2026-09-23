<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\Architecture\Concerns\ScansPhpSources;
use LimenAi\Tests\TestCase;

class ModuleBoundaryTest extends TestCase
{
    use ScansPhpSources;

    private string $src;

    protected function setUp(): void
    {
        parent::setUp();

        $this->src = dirname(__DIR__, 2).'/src';
    }

    public function test_contracts_do_not_import_concrete_implementations(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Contracts', [
            'LimenAi\\Runtime\\DefaultAgentRuntime',
            'LimenAi\\Tools\\ToolPipeline',
            'LimenAi\\Providers\\OpenAi\\OpenAiProvider',
            'LimenAi\\Broadcasting\\PusherBroadcaster',
        ]);
    }

    public function test_runtime_does_not_depend_on_ui_or_http_layers(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Runtime', [
            'LimenAi\\Http\\',
            'LimenAi\\Ui\\',
            'Illuminate\\View',
        ]);
    }

    public function test_runtime_does_not_depend_on_broadcasting_implementations(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Runtime', [
            'LimenAi\\Broadcasting\\',
            'Pusher\\',
        ]);
    }

    public function test_jobs_resolve_runtime_through_contracts_only(): void
    {
        foreach ($this->phpFilesIn($this->src.'/Jobs') as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString(
                'DefaultAgentRuntime',
                $contents,
                sprintf('Jobs must depend on AgentRuntime contract, not DefaultAgentRuntime (%s)', $file)
            );

            $this->assertStringContainsString(
                'LimenAi\\Contracts\\Runtime\\AgentRuntime',
                $contents,
                sprintf('Jobs must type-hint AgentRuntime contract (%s)', $file)
            );
        }
    }

    public function test_http_layer_does_not_embed_runtime_loop_logic(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Http', [
            'LimenAi\\Runtime\\DefaultAgentRuntime',
            'LimenAi\\Runtime\\ToolCallParser',
        ]);
    }

    public function test_observability_does_not_depend_on_http_controllers(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Observability', [
            'LimenAi\\Http\\Controllers\\',
        ]);
    }

    public function test_ui_layer_does_not_execute_tools_or_workflows_directly(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Ui', [
            'LimenAi\\Tools\\ToolPipeline',
            'LimenAi\\Workflows\\DefaultWorkflowEngine',
        ]);
    }

    public function test_declarative_http_executor_uses_ssrf_validator_contract(): void
    {
        $executor = $this->src.'/Integrations/DeclarativeHttpToolExecutor.php';
        $contents = file_get_contents($executor);

        $this->assertStringContainsString('LimenAi\\Contracts\\Security\\UrlValidator', $contents);
    }

    public function test_providers_do_not_reference_host_app_or_blade(): void
    {
        $this->assertNoForbiddenImports($this->src.'/Providers', [
            'App\\',
            'Illuminate\\View',
            'Pusher\\',
        ]);
    }

    public function test_authorization_does_not_trust_llm_output_for_identity(): void
    {
        foreach ($this->phpFilesIn($this->src.'/Authorization') as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString(
                'input[\'user_id\']',
                $contents,
                sprintf('Authorization must not read user_id from tool input (%s)', $file)
            );
        }
    }

    public function test_pusher_sdk_is_not_used_outside_broadcasting_module(): void
    {
        $this->assertForbiddenImportsOnlyInAllowedPaths(
            $this->src,
            'Pusher\\',
            ['src/Broadcasting'],
        );
    }

    public function test_blade_facade_is_not_used_outside_service_provider(): void
    {
        $this->assertForbiddenImportsOnlyInAllowedPaths(
            $this->src,
            'Illuminate\\Support\\Facades\\Blade',
            ['src/LimenAiServiceProvider.php'],
        );
    }
}
