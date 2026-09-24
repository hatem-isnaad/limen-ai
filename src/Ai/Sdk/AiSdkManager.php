<?php

namespace LimenAi\Ai\Sdk;

use Illuminate\Support\Str;
use LimenAi\Ai\AnonymousAgentRegistrar;
use LimenAi\Ai\Contracts\Agent;
use LimenAi\Contracts\Ai\AudioTranscriber;
use LimenAi\Contracts\Ai\DocumentReranker;
use LimenAi\Contracts\Ai\ImageGenerator;
use LimenAi\Contracts\Ai\SpeechGenerator;
use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Providers\EmbeddingProviderManager;
use LimenAi\Providers\Fake\FakeAudioTranscriber;
use LimenAi\Providers\Fake\FakeDocumentReranker;
use LimenAi\Providers\Fake\FakeImageProvider;
use LimenAi\Providers\Fake\FakeSpeechGenerator;
use LimenAi\Providers\OpenAi\OpenAiAudioTranscriber;
use LimenAi\Providers\OpenAi\OpenAiImageProvider;
use LimenAi\Providers\Cohere\CohereDocumentReranker;
use LimenAi\Providers\Jina\JinaDocumentReranker;
use LimenAi\Providers\OpenAi\OpenAiSpeechGenerator;
use LimenAi\Providers\ElevenLabs\ElevenLabsAudioTranscriber;
use LimenAi\Providers\ElevenLabs\ElevenLabsSpeechGenerator;
use LimenAi\Providers\Voyage\VoyageDocumentReranker;
use LimenAi\Providers\Gemini\GeminiImageProvider;
use LimenAi\Knowledge\KnowledgeService;
use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Runtime\RunContextData;

final class AiSdkManager
{
    public function __construct(
        private readonly EmbeddingProviderManager $embeddings,
        private readonly AgentRuntime $runtime,
        private readonly RunRepository $runs,
        private readonly AnonymousAgentRegistrar $anonymousAgents,
        private readonly KnowledgeService $knowledge,
        private readonly VectorStore $vectorStore,
    ) {}

    public function image(?Lab $provider = null): ImageGenerator
    {
        $name = ($provider ?? Lab::Fake)->value;
        $config = config("limen-ai.providers.{$name}", []);

        if ($name === Lab::OpenAi->value && is_array($config)) {
            return new OpenAiImageProvider($config);
        }

        if ($name === Lab::Gemini->value && is_array($config)) {
            return new GeminiImageProvider($config);
        }

        return new FakeImageProvider;
    }

    public function audio(?Lab $provider = null): SpeechGenerator
    {
        $name = ($provider ?? Lab::Fake)->value;
        $config = config("limen-ai.providers.{$name}", []);

        if ($name === Lab::OpenAi->value && is_array($config)) {
            return new OpenAiSpeechGenerator($config);
        }

        if ($name === Lab::ElevenLabs->value && is_array($config)) {
            return new ElevenLabsSpeechGenerator($config);
        }

        return new FakeSpeechGenerator;
    }

    public function transcription(?Lab $provider = null): AudioTranscriber
    {
        $name = ($provider ?? Lab::Fake)->value;
        $config = config("limen-ai.providers.{$name}", []);

        if ($name === Lab::OpenAi->value && is_array($config)) {
            return new OpenAiAudioTranscriber($config);
        }

        if ($name === Lab::ElevenLabs->value && is_array($config)) {
            return new ElevenLabsAudioTranscriber($config);
        }

        return new FakeAudioTranscriber;
    }

    public function embeddings(?string $provider = null): EmbeddingProvider
    {
        return $this->embeddings->driver($provider);
    }

    /**
     * @param  list<string>  $documents
     * @return list<array{index: int, document: string, score: float}>
     */
    public function rerank(string $query, array $documents, int $topK = 5, ?Lab $provider = null): array
    {
        $lab = $provider ?? Lab::tryFrom((string) config('limen-ai.rerank.default', 'fake')) ?? Lab::Fake;

        return match ($lab) {
            Lab::Cohere => (new CohereDocumentReranker(config('limen-ai.providers.cohere', [])))->rerank($query, $documents, $topK),
            Lab::Jina => (new JinaDocumentReranker(config('limen-ai.providers.jina', [])))->rerank($query, $documents, $topK),
            Lab::Voyage => (new VoyageDocumentReranker(config('limen-ai.providers.voyage', [])))->rerank($query, $documents, $topK),
            default => (new FakeDocumentReranker)->rerank($query, $documents, $topK),
        };
    }

    public function vectorStore(string $collection): VectorStoreClient
    {
        return new VectorStoreClient(
            $collection,
            $this->knowledge,
            $this->vectorStore,
            $this->embeddings->driver(),
        );
    }

    public function files(string $collection): FileClient
    {
        return new FileClient($collection, $this->knowledge);
    }

    /**
     * @param  list<string>  $labels
     * @return array{label: string, scores: array<string, float>}
     */
    public function classify(string $text, array $labels, ?string $agentKey = null): array
    {
        $agentKey ??= (string) config('limen-ai.default_agent', 'example');
        $prompt = "Classify the text into exactly one label from: ".implode(', ', $labels).".\n\nText:\n{$text}\n\nReply with only the label.";

        $runId = $this->runtime->run(
            $agentKey,
            (string) Str::uuid(),
            $prompt,
            RunContextData::make(['user_id' => auth()->id()]),
        );

        $run = $this->runs->find($runId);
        $label = trim((string) ($run['final_message'] ?? $labels[0]));

        if (! in_array($label, $labels, true)) {
            $label = $labels[0];
        }

        $scores = [];
        foreach ($labels as $candidate) {
            $scores[$candidate] = $candidate === $label ? 1.0 : 0.0;
        }

        return ['label' => $label, 'scores' => $scores];
    }

    /** @param  list<class-string|\LimenAi\Contracts\Tools\Tool>  $tools */
    public function anonymous(
        string $instructions,
        array $tools = [],
        ?string $model = null,
        ?string $provider = null,
    ): DynamicPromptAgent {
        $key = 'anonymous-'.Str::uuid();

        return $this->anonymousAgents->register($key, $instructions, $tools, $model, $provider);
    }

    public function agent(string|Agent $agent): Agent
    {
        return is_string($agent) ? app($agent) : $agent;
    }
}
