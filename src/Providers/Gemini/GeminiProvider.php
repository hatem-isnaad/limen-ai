<?php

namespace LimenAi\Providers\Gemini;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Providers\LlmResponse;
use LimenAi\Exceptions\ProviderException;
use LimenAi\Providers\Concerns\BuildsHttpClient;
use LimenAi\Providers\LlmResponseData;

class GeminiProvider implements LlmProvider
{
    use BuildsHttpClient;

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

        $model = (string) ($options['model'] ?? $this->config['model'] ?? 'gemini-1.5-flash');

        $payload = array_filter([
            'systemInstruction' => $this->systemInstruction($messages),
            'contents' => $this->mapContents($messages),
            'tools' => $tools !== [] ? [['functionDeclarations' => $this->mapTools($tools)]] : null,
            'generationConfig' => array_filter([
                'temperature' => $options['temperature'] ?? null,
                'maxOutputTokens' => $options['max_tokens'] ?? null,
            ], fn ($value) => $value !== null),
        ], fn ($value) => $value !== null);

        $response = $this->client()->post(
            '/models/'.urlencode($model).':generateContent',
            $payload,
        );

        if ($response->failed()) {
            throw new ProviderException(sprintf(
                'Gemini request failed with status %s: %s',
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
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');

        return Http::baseUrl($baseUrl)
            ->withQueryParameters([
                'key' => (string) ($this->config['api_key'] ?? ''),
            ])
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 60));
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    protected function systemInstruction(array $messages): ?array
    {
        $system = '';

        foreach ($messages as $message) {
            if (! is_array($message) || ($message['role'] ?? '') !== 'system') {
                continue;
            }

            $system .= ($system !== '' ? "\n\n" : '').(string) ($message['content'] ?? '');
        }

        if ($system === '') {
            return null;
        }

        return ['parts' => [['text' => $system]]];
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    protected function mapContents(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $role = (string) ($message['role'] ?? '');

            if ($role === 'system') {
                continue;
            }

            if ($role === 'tool') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [[
                        'functionResponse' => [
                            'name' => (string) ($message['name'] ?? 'tool'),
                            'response' => ['result' => (string) ($message['content'] ?? '')],
                        ],
                    ]],
                ];

                continue;
            }

            if ($role === 'assistant' && isset($message['tool_calls']) && is_array($message['tool_calls'])) {
                $parts = [];

                if (($message['content'] ?? '') !== '') {
                    $parts[] = ['text' => (string) $message['content']];
                }

                foreach ($message['tool_calls'] as $toolCall) {
                    if (! is_array($toolCall)) {
                        continue;
                    }

                    $function = is_array($toolCall['function'] ?? null) ? $toolCall['function'] : [];
                    $arguments = $function['arguments'] ?? '{}';
                    $decoded = is_string($arguments) ? json_decode($arguments, true) : $arguments;

                    $parts[] = [
                        'functionCall' => [
                            'name' => (string) ($function['name'] ?? ''),
                            'args' => is_array($decoded) ? $decoded : new \stdClass,
                        ],
                    ];
                }

                $contents[] = ['role' => 'model', 'parts' => $parts];

                continue;
            }

            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        return $contents;
    }

    /**
     * @param  list<array<string, mixed>>  $tools
     * @return list<array<string, mixed>>
     */
    protected function mapTools(array $tools): array
    {
        return array_values(array_map(function (array $tool): array {
            $function = is_array($tool['function'] ?? null) ? $tool['function'] : [];

            return [
                'name' => (string) ($function['name'] ?? ''),
                'description' => (string) ($function['description'] ?? ''),
                'parameters' => is_array($function['parameters'] ?? null)
                    ? $function['parameters']
                    : ['type' => 'object', 'properties' => new \stdClass],
            ];
        }, $tools));
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function mapResponse(?array $payload): LlmResponse
    {
        $candidate = $payload['candidates'][0] ?? null;

        if (! is_array($candidate)) {
            throw new ProviderException('Gemini response did not include a candidate.');
        }

        $content = '';
        $toolCalls = [];

        foreach ($candidate['content']['parts'] ?? [] as $part) {
            if (! is_array($part)) {
                continue;
            }

            if (isset($part['text'])) {
                $content .= (string) $part['text'];
            }

            if (isset($part['functionCall']) && is_array($part['functionCall'])) {
                $toolCalls[] = [
                    'id' => (string) ($part['functionCall']['name'] ?? 'tool'),
                    'type' => 'function',
                    'function' => [
                        'name' => (string) ($part['functionCall']['name'] ?? ''),
                        'arguments' => json_encode($part['functionCall']['args'] ?? new \stdClass, JSON_THROW_ON_ERROR),
                    ],
                ];
            }
        }

        $usage = $payload['usageMetadata'] ?? [];

        return LlmResponseData::fromArray([
            'content' => $content !== '' ? $content : null,
            'tool_calls' => $toolCalls,
            'usage' => [
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => $usage['totalTokenCount'] ?? 0,
            ],
            'finish_reason' => $candidate['finishReason'] ?? null,
        ]);
    }

    protected function ensureConfigured(): void
    {
        $this->ensureApiKey($this->config, 'Gemini');
    }
}
