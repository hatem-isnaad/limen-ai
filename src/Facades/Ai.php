<?php

namespace LimenAi\Facades;

use Illuminate\Support\Facades\Facade;
use LimenAi\Ai\Sdk\AiSdkManager;

/**
 * @method static \LimenAi\Contracts\Ai\ImageGenerator image(?\LimenAi\Ai\Sdk\Lab $provider = null)
 * @method static \LimenAi\Contracts\Ai\SpeechGenerator audio(?\LimenAi\Ai\Sdk\Lab $provider = null)
 * @method static \LimenAi\Contracts\Ai\AudioTranscriber transcription(?\LimenAi\Ai\Sdk\Lab $provider = null)
 * @method static \LimenAi\Contracts\Providers\EmbeddingProvider embeddings(?string $provider = null)
 * @method static list<array{index: int, document: string, score: float}> rerank(string $query, array $documents, int $topK = 5, ?\LimenAi\Ai\Sdk\Lab $provider = null)
 * @method static \LimenAi\Ai\Sdk\VectorStoreClient vectorStore(string $collection)
 * @method static \LimenAi\Ai\Sdk\FileClient files(string $collection)
 * @method static array classify(string $text, array $labels, ?string $agentKey = null)
 * @method static \LimenAi\Ai\Sdk\DynamicPromptAgent anonymous(string $instructions, array $tools = [], ?string $model = null, ?string $provider = null)
 * @method static \LimenAi\Ai\Contracts\Agent agent(string|\LimenAi\Ai\Contracts\Agent $agent)
 *
 * @see AiSdkManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiSdkManager::class;
    }
}
