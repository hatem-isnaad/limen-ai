<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Providers\LlmProviderManager;
use LimenAi\Providers\OpenAi\OpenAiProvider;
use LimenAi\Tests\TestCase;

class OpenRouterProviderTest extends TestCase
{
    public function test_it_resolves_openrouter_via_openai_compatible_driver(): void
    {
        config()->set('limen-ai.providers.openrouter.api_key', 'test-key');

        $provider = app(LlmProviderManager::class)->driver('openrouter');

        $this->assertInstanceOf(OpenAiProvider::class, $provider);
        $this->assertSame('openrouter', $provider->name());
    }

    public function test_it_calls_openrouter_base_url(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'OpenRouter response'],
                    'finish_reason' => 'stop',
                ]],
                'usage' => ['total_tokens' => 10],
            ], 200),
        ]);

        config()->set('limen-ai.providers.openrouter', [
            'driver' => 'openrouter',
            'api_key' => 'test-key',
            'base_url' => 'https://openrouter.ai/api/v1',
        ]);

        $provider = app(LlmProviderManager::class)->driver('openrouter');

        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ], [], [
            'model' => 'anthropic/claude-3.5-sonnet',
        ]);

        $this->assertSame('OpenRouter response', $response->content());
    }
}
