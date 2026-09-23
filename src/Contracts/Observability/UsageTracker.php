<?php

namespace LimenAi\Contracts\Observability;

interface UsageTracker
{
    public function recordLlmUsage(string $runId, string $provider, string $model, array $usage): void;

    public function recordToolExecution(string $runId, string $toolKey, int $durationMs): void;
}
