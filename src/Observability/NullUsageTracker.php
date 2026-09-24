<?php

namespace LimenAi\Observability;

use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Observability\UsageTracker;

class NullUsageTracker implements UsageTracker, UsageReader
{
    public function recordLlmUsage(string $runId, string $provider, string $model, array $usage): void
    {
    }

    public function recordToolExecution(string $runId, string $toolKey, int $durationMs): void
    {
    }

    /** @return list<array<string, mixed>> */
    public function recordsForRun(string $runId): array
    {
        return [];
    }

    public function summarizeForRun(string $runId): array
    {
        return UsageSummary::fromRecords([]);
    }

    public function summarize(): array
    {
        return UsageSummary::fromRecords([]);
    }
}
