<?php

namespace LimenAi\Observability;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Log;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Observability\UsageTracker;

class LogUsageTracker implements UsageReader, UsageTracker
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly UsageBuffer $buffer,
    ) {}

    public function recordLlmUsage(string $runId, string $provider, string $model, array $usage): void
    {
        if (! $this->enabled()) {
            return;
        }

        $record = [
            'type' => 'llm',
            'run_id' => $runId,
            'provider' => $provider,
            'model' => $model,
            'usage' => $usage,
        ];

        $this->buffer->push($record);
        Log::info('[limen-ai] usage.llm', $record);
    }

    public function recordToolExecution(string $runId, string $toolKey, int $durationMs): void
    {
        if (! $this->enabled()) {
            return;
        }

        $record = [
            'type' => 'tool',
            'run_id' => $runId,
            'tool_key' => $toolKey,
            'duration_ms' => $durationMs,
        ];

        $this->buffer->push($record);
        Log::info('[limen-ai] usage.tool', $record);
    }

    /** @return list<array<string, mixed>> */
    public function recordsForRun(string $runId): array
    {
        return $this->buffer->forRun($runId);
    }

    protected function enabled(): bool
    {
        return (bool) $this->config->get('limen-ai.observability.usage_tracking_enabled', true);
    }
}
