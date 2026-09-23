<?php

namespace LimenAi\Tests\Unit\Providers;

use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\Fake\FakeLlmProvider;
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

    public function test_it_throws_for_unknown_provider(): void
    {
        $this->expectException(ProviderConfigurationException::class);

        app(LlmProviderManager::class)->driver('missing-provider');
    }
}
