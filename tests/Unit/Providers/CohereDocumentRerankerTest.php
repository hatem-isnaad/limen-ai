<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Ai\Sdk\Lab;
use LimenAi\Ai\Sdk\AiSdkManager;
use LimenAi\Tests\TestCase;

class CohereDocumentRerankerTest extends TestCase
{
    public function test_cohere_rerank_maps_scores(): void
    {
        Http::fake([
            'api.cohere.com/*' => Http::response([
                'results' => [
                    ['index' => 1, 'relevance_score' => 0.9],
                    ['index' => 0, 'relevance_score' => 0.1],
                ],
            ]),
        ]);

        config()->set('limen-ai.rerank.default', Lab::Cohere->value);
        config()->set('limen-ai.providers.cohere', ['api_key' => 'test-key']);

        $ranked = app(AiSdkManager::class)->rerank('query', ['first', 'second'], 2);

        $this->assertSame(1, $ranked[0]['index']);
        $this->assertSame('second', $ranked[0]['document']);
        $this->assertSame(0.9, $ranked[0]['score']);
    }
}
