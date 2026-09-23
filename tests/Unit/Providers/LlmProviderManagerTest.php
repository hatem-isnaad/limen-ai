<?php

namespace LimenAi\Tests\Unit\Providers;

use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\Anthropic\AnthropicProvider;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\Gemini\GeminiProvider;
use LimenAi\Providers\LlmProviderManager;
use LimenAi\Providers\OpenAi\OpenAiProvider;
use LimenAi\Tests\TestCase;

class LlmProviderManagerTest extends TestCase
{
    public function test_it_resolves_fake_driver_by_default(): void
    {
        $manager = app(LlmProviderManager::class);

        $provider = $manager->driver();

        $this->assertInstanceOf(FakeLlmProvider::class, $provider);
        $this->assertSame('fake', $provider->name());
    }

    public function test_it_resolves_openai_driver_from_config(): void
    {
        config()->set('limen-ai.providers.openai.api_key', 'test-key');

        $manager = app(LlmProviderManager::class);
        $provider = $manager->driver('openai');

        $this->assertInstanceOf(OpenAiProvider::class, $provider);
        $this->assertSame('openai', $provider->name());
    }

    public function test_it_resolves_anthropic_gemini_and_openrouter_drivers(): void
    {
        config()->set('limen-ai.providers.anthropic.api_key', 'anthropic-key');
        config()->set('limen-ai.providers.gemini.api_key', 'gemini-key');
        config()->set('limen-ai.providers.openrouter.api_key', 'openrouter-key');

        $manager = app(LlmProviderManager::class);

        $this->assertInstanceOf(AnthropicProvider::class, $manager->driver('anthropic'));
        $this->assertInstanceOf(GeminiProvider::class, $manager->driver('gemini'));
        $this->assertInstanceOf(OpenAiProvider::class, $manager->driver('openrouter'));
    }

    public function test_it_throws_for_unknown_provider(): void
    {
        $this->expectException(ProviderConfigurationException::class);

        app(LlmProviderManager::class)->driver('missing-provider');
    }

    public function test_it_throws_for_unregistered_driver_class(): void
    {
        config()->set('limen-ai.providers.custom', [
            'driver' => 'missing-driver',
        ]);

        $this->expectException(ProviderConfigurationException::class);

        app(LlmProviderManager::class)->driver('custom');
    }
}
