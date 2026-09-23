<?php

namespace LimenAi\Providers\OpenAi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Providers\LlmResponse;
use LimenAi\Exceptions\ProviderConfigurationException;
use LimenAi\Exceptions\ProviderException;
use LimenAi\Providers\LlmResponseData;

class OpenAiProvider implements LlmProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $name,
        private readonly array $config,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function chat(array $messages, array $tools = [], array $options = []): LlmResponse
    {
        $this->ensureConfigured();

        $payload = array_filter([
            'model' => $options['model'] ?? null,
            'messages' => $messages,
            'tools' => $tools !== [] ? $tools : null,
            'tool_choice' => $options['tool_choice'] ?? null,
            'temperature' => $options['temperature'] ?? null,
            'max_tokens' => $options['max_tokens'] ?? null,
        ], fn ($value) => $value !== null);

        $response = $this->client()->post('/chat/completions', $payload);

        if ($response->failed()) {
            throw new ProviderException(sprintf(
                'OpenAI request failed with status %s: %s',
                $response->status(),
                $response->body(),
            ));
        }

        return $this->mapResponse($response->json());
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    protected function client(): PendingRequest
    {
        $headers = array_filter([
            'Authorization' => isset($this->config['api_key']) ? 'Bearer '.$this->config['api_key'] : null,
            'OpenAI-Organization' => $this->config['organization'] ?? null,
        ]);

        return Http::baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.openai.com/v1'), '/'))
            ->withHeaders($headers)
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 60));
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function mapResponse(?array $payload): LlmResponse
    {
        $choice = $payload['choices'][0] ?? null;

        if (! is_array($choice)) {
            throw new ProviderException('OpenAI response did not include a completion choice.');
        }

        $message = $choice['message'] ?? [];

        return LlmResponseData::fromArray([
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? [],
            'usage' => $payload['usage'] ?? [],
            'finish_reason' => $choice['finish_reason'] ?? null,
        ]);
    }

    protected function ensureConfigured(): void
    {
        if (empty($this->config['api_key'])) {
            throw new ProviderConfigurationException('OpenAI provider is missing an API key.');
        }
    }
}
