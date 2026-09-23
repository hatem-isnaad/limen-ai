<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Providers\EmbeddingProviderManager;
use LimenAi\Providers\Fake\FakeEmbeddingProvider;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmProviderManager;
use LimenAi\Tests\TestCase;

class ProviderBindingsTest extends TestCase
{
    public function test_llm_and_embedding_contracts_resolve_to_fake_drivers(): void
    {
        $this->assertInstanceOf(FakeLlmProvider::class, app(LlmProvider::class));
        $this->assertInstanceOf(FakeEmbeddingProvider::class, app(EmbeddingProvider::class));
        $this->assertInstanceOf(LlmProviderManager::class, app(LlmProviderManager::class));
        $this->assertInstanceOf(EmbeddingProviderManager::class, app(EmbeddingProviderManager::class));
    }
}
