<?php

namespace LimenAi\Tests\Unit\Integrations;

use Illuminate\Support\Facades\Http;
use LimenAi\Exceptions\HttpIntegrationException;
use LimenAi\Integrations\DeclarativeHttpToolExecutor;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class DeclarativeHttpToolExecutorTest extends TestCase
{
    public function test_it_executes_get_request_and_returns_json_body(): void
    {
        Http::fake([
            'https://api.example.com/status/shipments*' => Http::response(['status' => 'ok'], 200),
        ]);

        $result = app(DeclarativeHttpToolExecutor::class)->execute(
            [
                'connector' => 'example_api',
                'method' => 'GET',
                'path' => '/status/{{ input.resource }}',
                'query' => ['include' => '{{ input.include }}'],
                'timeout' => 10,
            ],
            ['resource' => 'shipments', 'include' => 'details'],
            RunContextData::make()->forToolExecution('run-1', 'conv-1', 'example'),
        );

        $this->assertSame(200, $result['status']);
        $this->assertSame('ok', $result['body']['status']);
    }

    public function test_it_wraps_non_json_responses_in_raw_key(): void
    {
        Http::fake([
            'https://api.example.com/ping' => Http::response('pong', 200),
        ]);

        config()->set('limen-ai.integrations.connectors.example_api.base_url', 'https://api.example.com');

        $result = app(DeclarativeHttpToolExecutor::class)->execute(
            [
                'connector' => 'example_api',
                'method' => 'GET',
                'path' => '/ping',
            ],
            [],
            RunContextData::make(),
        );

        $this->assertSame(['raw' => 'pong'], $result['body']);
    }

    public function test_it_throws_when_connector_missing(): void
    {
        $this->expectException(HttpIntegrationException::class);

        app(DeclarativeHttpToolExecutor::class)->execute(
            ['connector' => ''],
            [],
            RunContextData::make(),
        );
    }

    public function test_it_throws_when_connector_not_found(): void
    {
        $this->expectException(HttpIntegrationException::class);
        $this->expectExceptionMessage('HTTP connector [missing_connector] was not found');

        app(DeclarativeHttpToolExecutor::class)->execute(
            ['connector' => 'missing_connector'],
            [],
            RunContextData::make(),
        );
    }

    public function test_it_throws_when_request_fails(): void
    {
        Http::fake([
            'https://api.example.com/status/shipments*' => Http::response('error', 500),
        ]);

        $this->expectException(HttpIntegrationException::class);

        app(DeclarativeHttpToolExecutor::class)->execute(
            [
                'connector' => 'example_api',
                'method' => 'GET',
                'path' => '/status/{{ input.resource }}',
            ],
            ['resource' => 'shipments'],
            RunContextData::make(),
        );
    }

    public function test_it_throws_for_unsupported_http_method(): void
    {
        $this->expectException(HttpIntegrationException::class);
        $this->expectExceptionMessage('Unsupported HTTP method');

        app(DeclarativeHttpToolExecutor::class)->execute(
            [
                'connector' => 'example_api',
                'method' => 'OPTIONS',
                'path' => '/status/test',
            ],
            [],
            RunContextData::make(),
        );
    }

    public function test_it_blocks_ssrf_targets(): void
    {
        config()->set('limen-ai.security.ssrf.resolve_dns', false);
        config()->set('limen-ai.integrations.connectors.example_api.base_url', 'http://127.0.0.1');

        $this->expectException(HttpIntegrationException::class);

        app(DeclarativeHttpToolExecutor::class)->execute(
            [
                'connector' => 'example_api',
                'method' => 'GET',
                'path' => '/internal',
            ],
            [],
            RunContextData::make(),
        );
    }

    public function test_it_executes_post_put_patch_and_delete_methods(): void
    {
        Http::fake([
            'https://api.example.com/create' => Http::response(['id' => 1], 201),
            'https://api.example.com/update' => Http::response(['updated' => true], 200),
            'https://api.example.com/patch' => Http::response(['patched' => true], 200),
            'https://api.example.com/remove' => Http::response([], 204),
        ]);

        config()->set('limen-ai.integrations.connectors.example_api.base_url', 'https://api.example.com');

        $executor = app(DeclarativeHttpToolExecutor::class);
        $context = RunContextData::make();
        $base = ['connector' => 'example_api'];

        $post = $executor->execute(array_merge($base, ['method' => 'POST', 'path' => '/create']), ['name' => 'x'], $context);
        $put = $executor->execute(array_merge($base, ['method' => 'PUT', 'path' => '/update']), ['name' => 'y'], $context);
        $patch = $executor->execute(array_merge($base, ['method' => 'PATCH', 'path' => '/patch']), ['name' => 'z'], $context);
        $delete = $executor->execute(array_merge($base, ['method' => 'DELETE', 'path' => '/remove']), [], $context);

        $this->assertSame(201, $post['status']);
        $this->assertSame(200, $put['status']);
        $this->assertSame(200, $patch['status']);
        $this->assertSame(204, $delete['status']);
    }
}
