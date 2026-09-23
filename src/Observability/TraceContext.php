<?php

namespace LimenAi\Observability;

use Illuminate\Support\Str;

final class TraceContext
{
    public function __construct(
        public readonly string $traceId,
        public readonly string $spanId,
        public readonly ?string $parentSpanId = null,
    ) {}

    public static function forRun(): self
    {
        return new self((string) Str::uuid(), (string) Str::uuid());
    }

    public static function child(string $traceId, ?string $parentSpanId = null): self
    {
        return new self($traceId, (string) Str::uuid(), $parentSpanId);
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'trace_id' => $this->traceId,
            'span_id' => $this->spanId,
            'parent_span_id' => $this->parentSpanId,
        ];
    }
}
