<?php

namespace LimenAi\Contracts\Observability;

interface UsageReader
{
    /** @return list<array<string, mixed>> */
    public function recordsForRun(string $runId): array;
}
