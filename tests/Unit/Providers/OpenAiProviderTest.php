<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\OpenAi\OpenAiProvider;
use LimenAi\Tests\TestCase;

class OpenAiProviderTest extends TestCase
{
    public function test_it_maps_successful_chat_completion_response(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Shipment 12345 is in transit.',
                    ],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [
                    'prompt_tokens' => 20,
                    'completion_tokens' => 10,
                    'total_tokens' => 30,
                ],
            ], 200),
        ]);

        $provider = new OpenAiProvider('openai', [
            'api_key' => 'test-key',
            'base_url' => 'https://api.openai.com/v1',
        ]);

        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Where is shipment 12345?'],
        ], [], [
            'model' => 'gpt-4.1-mini',
        ]);

        $this->assertSame('Shipment 12345 is in transit.', $response->content());
        $this->assertSame(30, $response->usage()['total_tokens']);
        $this->assertSame('stop', $response->finishReason());
    }

    public function test_it_requires_api_key_before_calling_openai(): void
    {
        $provider = new OpenAiProvider('openai', [
            'api_key' => null,
        ]);

        $this->expectException(ProviderConfigurationException::class);

        $provider->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ]);
    }
}
