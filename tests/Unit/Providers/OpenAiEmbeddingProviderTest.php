<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Providers\EmbeddingProviderManager;
use LimenAi\Providers\OpenAi\OpenAiEmbeddingProvider;
use LimenAi\Tests\TestCase;

class OpenAiEmbeddingProviderTest extends TestCase
{
    public function test_it_resolves_openai_embedding_driver_from_config(): void
    {
        config()->set('limen-ai.embeddings.default', 'openai');
        config()->set('limen-ai.embeddings.providers.openai.api_key', 'test-key');

        $provider = app(EmbeddingProviderManager::class)->driver('openai');

        $this->assertInstanceOf(OpenAiEmbeddingProvider::class, $provider);
        $this->assertSame('openai', $provider->name());
    }

    public function test_it_maps_embedding_vectors_in_request_order(): void
    {
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['index' => 1, 'embedding' => [0.2, 0.4]],
                    ['index' => 0, 'embedding' => [0.1, 0.3]],
                ],
            ], 200),
        ]);

        $provider = new OpenAiEmbeddingProvider('openai', [
            'api_key' => 'test-key',
            'model' => 'text-embedding-3-small',
        ]);

        $vectors = $provider->embed(['first', 'second']);

        $this->assertSame([0.1, 0.3], $vectors[0]);
        $this->assertSame([0.2, 0.4], $vectors[1]);
    }
}
