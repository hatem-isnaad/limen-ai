<?php

namespace LimenAi\Tests\Unit\Integrations;

use LimenAi\Integrations\ConfigHttpConnector;
use LimenAi\Integrations\HttpRequestBuilder;
use LimenAi\Tests\TestCase;

class HttpRequestBuilderTest extends TestCase
{
    public function test_it_builds_templated_urls_and_query_parameters(): void
    {
        $builder = app(HttpRequestBuilder::class);
        $connector = ConfigHttpConnector::fromConfig('example_api', [
            'base_url' => 'https://api.example.com',
            'headers' => ['Accept' => 'application/json'],
            'authentication' => [
                'type' => 'bearer',
                'token' => 'test-token',
            ],
        ]);

        $request = $builder->build($connector, [
            'method' => 'GET',
            'path' => '/status/{{ input.resource }}',
            'query' => ['include' => '{{ input.include }}'],
        ], [
            'resource' => 'shipments',
            'include' => 'details',
        ]);

        $this->assertSame('GET', $request['method']);
        $this->assertSame('https://api.example.com/status/shipments', $request['url']);
        $this->assertSame('details', $request['query']['include']);
        $this->assertSame('Bearer test-token', $request['headers']['Authorization']);
    }
}
