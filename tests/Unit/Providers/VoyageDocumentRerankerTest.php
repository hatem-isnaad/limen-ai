<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Ai\Sdk\AiSdkManager;
use LimenAi\Ai\Sdk\Lab;
use LimenAi\Tests\TestCase;

class VoyageDocumentRerankerTest extends TestCase
{
    public function test_voyage_rerank_maps_scores(): void
    {
        Http::fake([
            'api.voyageai.com/*' => Http::response([
                'data' => [
                    ['index' => 0, 'relevance_score' => 0.95],
                ],
            ]),
        ]);

        config()->set('limen-ai.rerank.default', Lab::Voyage->value);
        config()->set('limen-ai.providers.voyage', ['api_key' => 'test-key']);

        $ranked = app(AiSdkManager::class)->rerank('query', ['doc'], 1);

        $this->assertSame(0, $ranked[0]['index']);
        $this->assertSame(0.95, $ranked[0]['score']);
    }
}
