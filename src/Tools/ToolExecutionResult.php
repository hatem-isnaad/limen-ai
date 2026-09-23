<?php

namespace LimenAi\Tools;

final class ToolExecutionResult
{
    /**
     * @param  array<string, mixed>  $output
     */
    public function __construct(
        private readonly bool $fromCache,
        private readonly array $output,
        private readonly int $durationMs,
        private readonly string $executionId,
    ) {}

    public static function cached(array $output, string $executionId): self
    {
        return new self(true, $output, 0, $executionId);
    }

    public static function fresh(array $output, int $durationMs, string $executionId): self
    {
        return new self(false, $output, $durationMs, $executionId);
    }

    public function fromCache(): bool
    {
        return $this->fromCache;
    }

    /** @return array<string, mixed> */
    public function output(): array
    {
        return $this->output;
    }

    public function durationMs(): int
    {
        return $this->durationMs;
    }

    public function executionId(): string
    {
        return $this->executionId;
    }
}
