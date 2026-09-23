<?php

namespace LimenAi\Observability;

class AuditBuffer
{
    /** @var list<array<string, mixed>> */
    private array $entries = [];

    /** @param  array<string, mixed>  $context */
    public function push(string $action, array $context): void
    {
        $this->entries[] = [
            'action' => $action,
            'context' => $context,
            'logged_at' => now()->toIso8601String(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->entries;
    }

    /** @return list<array<string, mixed>> */
    public function forRun(string $runId): array
    {
        return array_values(array_filter(
            $this->entries,
            fn (array $entry): bool => ($entry['context']['run_id'] ?? null) === $runId,
        ));
    }

    public function flush(): void
    {
        $this->entries = [];
    }
}
