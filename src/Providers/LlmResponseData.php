<?php

namespace LimenAi\Providers;

use LimenAi\Contracts\Providers\LlmResponse;

final class LlmResponseData implements LlmResponse
{
    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  array<string, int>  $usage
     */
    public function __construct(
        private readonly ?string $content,
        private readonly array $toolCalls = [],
        private readonly array $usage = [],
        private readonly ?string $finishReason = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            content: isset($payload['content']) ? (string) $payload['content'] : null,
            toolCalls: array_values($payload['tool_calls'] ?? []),
            usage: $payload['usage'] ?? [],
            finishReason: isset($payload['finish_reason']) ? (string) $payload['finish_reason'] : null,
        );
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function toolCalls(): array
    {
        return $this->toolCalls;
    }

    public function usage(): array
    {
        return $this->usage;
    }

    public function finishReason(): ?string
    {
        return $this->finishReason;
    }
}
