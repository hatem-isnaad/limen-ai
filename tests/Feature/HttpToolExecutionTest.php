<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Http;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Tests\TestCase;

class HttpToolExecutionTest extends TestCase
{
    public function test_it_executes_declarative_http_tool_via_pipeline(): void
    {
        Http::fake([
            'https://api.example.com/status/shipments*' => Http::response([
                'resource' => 'shipments',
                'status' => 'in_transit',
            ], 200),
        ]);

        app(ToolRepository::class)->find('example_http_status');

        $result = app(ToolPipeline::class)->execute(
            'example_http_status',
            ['resource' => 'shipments', 'include' => 'details'],
            RunContextData::make(['user_id' => 1])->forToolExecution('run-1', 'conv-1', 'example'),
        );

        $this->assertSame('in_transit', $result->output()['body']['status']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.example.com/status/shipments?include=details'
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    public function test_it_blocks_ssrf_targets_before_requesting(): void
    {
        config()->set('limen-ai.security.ssrf.resolve_dns', false);
        config()->set('limen-ai.tools.example_http_status.integration.path', '/status/{{ input.resource }}');
        config()->set('limen-ai.integrations.connectors.example_api.base_url', 'http://127.0.0.1');

        $this->expectException(\LimenAi\Exceptions\ToolExecutionException::class);
        $this->expectExceptionMessage('not allowed');

        app(ToolPipeline::class)->execute(
            'example_http_status',
            ['resource' => 'internal'],
            RunContextData::make(['user_id' => 1])->forToolExecution('run-2', 'conv-2', 'example'),
        );
    }
}
