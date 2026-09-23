<?php

namespace LimenAi\Observability;

class UsageBuffer
{
    /** @var list<array<string, mixed>> */
    private array $records = [];

    /** @param  array<string, mixed>  $record */
    public function push(array $record): void
    {
        $this->records[] = array_merge($record, [
            'recorded_at' => now()->toIso8601String(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->records;
    }

    /** @return list<array<string, mixed>> */
    public function forRun(string $runId): array
    {
        return array_values(array_filter(
            $this->records,
            fn (array $record): bool => ($record['run_id'] ?? null) === $runId,
        ));
    }

    public function flush(): void
    {
        $this->records = [];
    }
}
