<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\Anthropic\AnthropicProvider;
use LimenAi\Tests\TestCase;

class AnthropicProviderTest extends TestCase
{
    public function test_it_maps_successful_message_response(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => 'Shipment 12345 is delayed.',
                ]],
                'stop_reason' => 'end_turn',
                'usage' => [
                    'input_tokens' => 18,
                    'output_tokens' => 12,
                ],
            ], 200),
        ]);

        $provider = new AnthropicProvider('anthropic', [
            'api_key' => 'test-key',
        ]);

        $response = $provider->chat([
            ['role' => 'system', 'content' => 'You are helpful.'],
            ['role' => 'user', 'content' => 'Status for shipment 12345?'],
        ], [], [
            'model' => 'claude-sonnet-4-20250514',
        ]);

        $this->assertSame('Shipment 12345 is delayed.', $response->content());
        $this->assertSame(30, $response->usage()['total_tokens']);
        $this->assertSame('end_turn', $response->finishReason());
    }

    public function test_it_maps_tool_use_blocks_to_openai_style_tool_calls(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [[
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'get_shipment_status',
                    'input' => ['shipment_id' => '12345'],
                ]],
                'stop_reason' => 'tool_use',
                'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
            ], 200),
        ]);

        $provider = new AnthropicProvider('anthropic', ['api_key' => 'test-key']);

        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Lookup shipment 12345'],
        ], [[
            'type' => 'function',
            'function' => [
                'name' => 'get_shipment_status',
                'description' => 'Lookup shipment',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['shipment_id' => ['type' => 'string']],
                ],
            ],
        ]], ['model' => 'claude-sonnet-4-20250514']);

        $this->assertSame('get_shipment_status', $response->toolCalls()[0]['function']['name']);
        $this->assertStringContainsString('12345', $response->toolCalls()[0]['function']['arguments']);
    }

    public function test_it_requires_api_key_before_calling_anthropic(): void
    {
        $provider = new AnthropicProvider('anthropic', ['api_key' => null]);

        $this->expectException(ProviderConfigurationException::class);

        $provider->chat([['role' => 'user', 'content' => 'Hello']]);
    }
}
