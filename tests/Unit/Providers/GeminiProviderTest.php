<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\Gemini\GeminiProvider;
use LimenAi\Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_it_maps_successful_generate_content_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'Your shipment is in transit.']],
                    ],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 12,
                    'candidatesTokenCount' => 8,
                    'totalTokenCount' => 20,
                ],
            ], 200),
        ]);

        $provider = new GeminiProvider('gemini', [
            'api_key' => 'test-key',
        ]);

        $response = $provider->chat([
            ['role' => 'system', 'content' => 'You are helpful.'],
            ['role' => 'user', 'content' => 'Where is my shipment?'],
        ], [], [
            'model' => 'gemini-1.5-flash',
        ]);

        $this->assertSame('Your shipment is in transit.', $response->content());
        $this->assertSame(20, $response->usage()['total_tokens']);
        $this->assertSame('STOP', $response->finishReason());
    }

    public function test_it_maps_function_call_blocks_to_tool_calls(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'functionCall' => [
                                'name' => 'get_shipment_status',
                                'args' => ['shipment_id' => '99'],
                            ],
                        ]],
                    ],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['totalTokenCount' => 10],
            ], 200),
        ]);

        $provider = new GeminiProvider('gemini', ['api_key' => 'test-key']);

        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Lookup shipment 99'],
        ], [[
            'type' => 'function',
            'function' => [
                'name' => 'get_shipment_status',
                'description' => 'Lookup shipment',
                'parameters' => ['type' => 'object', 'properties' => []],
            ],
        ]], ['model' => 'gemini-1.5-flash']);

        $this->assertSame('get_shipment_status', $response->toolCalls()[0]['function']['name']);
    }

    public function test_it_requires_api_key_before_calling_gemini(): void
    {
        $provider = new GeminiProvider('gemini', ['api_key' => null]);

        $this->expectException(ProviderConfigurationException::class);

        $provider->chat([['role' => 'user', 'content' => 'Hello']]);
    }
}
