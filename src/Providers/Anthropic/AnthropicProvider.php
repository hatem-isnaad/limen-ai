<?php

namespace LimenAi\Providers\Anthropic;

use Illuminate\Http\Client\PendingRequest;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Providers\LlmResponse;
use LimenAi\Exceptions\ProviderException;
use LimenAi\Providers\Concerns\BuildsHttpClient;
use LimenAi\Providers\LlmResponseData;

class AnthropicProvider implements LlmProvider
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

        [$system, $anthropicMessages] = $this->mapMessages($messages);

        $payload = array_filter([
            'model' => $options['model'] ?? null,
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'] ?? 4096,
            'system' => $system !== '' ? $system : null,
            'messages' => $anthropicMessages,
            'tools' => $tools !== [] ? $this->mapTools($tools) : null,
            'temperature' => $options['temperature'] ?? null,
        ], fn ($value) => $value !== null);

        $response = $this->client()->post('/messages', $payload);

        if ($response->failed()) {
            throw new ProviderException(sprintf(
                'Anthropic request failed with status %s: %s',
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
        return $this->buildHttpClient(
            $this->config,
            'https://api.anthropic.com/v1',
            [
                'x-api-key' => $this->config['api_key'] ?? null,
                'anthropic-version' => (string) ($this->config['api_version'] ?? '2023-06-01'),
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array{0: string, 1: list<array<string, mixed>>}
     */
    protected function mapMessages(array $messages): array
    {
        $system = '';
        $mapped = [];

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $role = (string) ($message['role'] ?? '');

            if ($role === 'system') {
                $system .= ($system !== '' ? "\n\n" : '').(string) ($message['content'] ?? '');

                continue;
            }

            if ($role === 'tool') {
                $mapped[] = [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'tool_result',
                        'tool_use_id' => (string) ($message['tool_call_id'] ?? ''),
                        'content' => (string) ($message['content'] ?? ''),
                    ]],
                ];

                continue;
            }

            if ($role === 'assistant' && isset($message['tool_calls']) && is_array($message['tool_calls'])) {
                $content = [];

                if (($message['content'] ?? '') !== '') {
                    $content[] = [
                        'type' => 'text',
                        'text' => (string) $message['content'],
                    ];
                }

                foreach ($message['tool_calls'] as $toolCall) {
                    if (! is_array($toolCall)) {
                        continue;
                    }

                    $function = is_array($toolCall['function'] ?? null) ? $toolCall['function'] : [];
                    $arguments = $function['arguments'] ?? '{}';
                    $decoded = is_string($arguments) ? json_decode($arguments, true) : $arguments;

                    $content[] = [
                        'type' => 'tool_use',
                        'id' => (string) ($toolCall['id'] ?? $function['name'] ?? 'tool'),
                        'name' => (string) ($function['name'] ?? ''),
                        'input' => is_array($decoded) ? $decoded : [],
                    ];
                }

                $mapped[] = ['role' => 'assistant', 'content' => $content];

                continue;
            }

            $mapped[] = [
                'role' => $role === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        return [$system, $mapped];
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
                'input_schema' => is_array($function['parameters'] ?? null)
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
        $content = '';
        $toolCalls = [];

        foreach ($payload['content'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            if (($block['type'] ?? '') === 'text') {
                $content .= (string) ($block['text'] ?? '');
            }

            if (($block['type'] ?? '') === 'tool_use') {
                $toolCalls[] = [
                    'id' => (string) ($block['id'] ?? $block['name'] ?? 'tool'),
                    'type' => 'function',
                    'function' => [
                        'name' => (string) ($block['name'] ?? ''),
                        'arguments' => json_encode($block['input'] ?? [], JSON_THROW_ON_ERROR),
                    ],
                ];
            }
        }

        $usage = $payload['usage'] ?? [];

        return LlmResponseData::fromArray([
            'content' => $content !== '' ? $content : null,
            'tool_calls' => $toolCalls,
            'usage' => [
                'prompt_tokens' => $usage['input_tokens'] ?? 0,
                'completion_tokens' => $usage['output_tokens'] ?? 0,
                'total_tokens' => ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0),
            ],
            'finish_reason' => $payload['stop_reason'] ?? null,
        ]);
    }

    protected function ensureConfigured(): void
    {
        $this->ensureApiKey($this->config, 'Anthropic');
    }
}
