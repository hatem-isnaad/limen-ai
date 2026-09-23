<?php

namespace LimenAi\Tests\Unit\Providers;

use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Providers\EmbeddingProviderManager;
use LimenAi\Providers\Fake\FakeEmbeddingProvider;
use LimenAi\Tests\TestCase;

class EmbeddingProviderManagerTest extends TestCase
{
    public function test_it_resolves_fake_driver_by_default(): void
    {
        $provider = app(EmbeddingProviderManager::class)->driver();

        $this->assertInstanceOf(FakeEmbeddingProvider::class, $provider);
    }

    public function test_it_caches_driver_instances(): void
    {
        $manager = app(EmbeddingProviderManager::class);

        $this->assertSame($manager->driver('fake'), $manager->driver('fake'));
    }

    public function test_it_throws_for_unconfigured_provider(): void
    {
        $this->expectException(ProviderConfigurationException::class);
        $this->expectExceptionMessage('not configured');

        app(EmbeddingProviderManager::class)->driver('missing-provider-'.uniqid());
    }

    public function test_it_throws_for_unsupported_custom_driver(): void
    {
        config()->set('limen-ai.embeddings.providers.custom_test', [
            'driver' => 'unknown_driver',
        ]);

        $this->expectException(ProviderConfigurationException::class);
        $this->expectExceptionMessage('not supported');

        app(EmbeddingProviderManager::class)->driver('custom_test');
    }

    public function test_it_resolves_custom_driver_from_config_map(): void
    {
        config()->set('limen-ai.embeddings.drivers.fake_alias', FakeEmbeddingProvider::class);
        config()->set('limen-ai.embeddings.providers.custom_fake', [
            'driver' => 'fake_alias',
        ]);

        $provider = app(EmbeddingProviderManager::class)->driver('custom_fake');

        $this->assertInstanceOf(EmbeddingProvider::class, $provider);
    }

    public function test_it_throws_when_custom_class_does_not_implement_contract(): void
    {
        config()->set('limen-ai.embeddings.drivers.bad', \stdClass::class);
        config()->set('limen-ai.embeddings.providers.bad_provider', [
            'driver' => 'bad',
        ]);

        $this->expectException(ProviderConfigurationException::class);
        $this->expectExceptionMessage('must implement EmbeddingProvider');

        app(EmbeddingProviderManager::class)->driver('bad_provider');
    }
}
