<?php

namespace LimenAi\Observability;

use LimenAi\Contracts\Observability\AuditExporter;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Runtime\RunRepository;

class RunObservabilityReporter
{
    public function __construct(
        private readonly RunRepository $runs,
        private readonly AuditExporter $auditExporter,
        private readonly UsageReader $usageReader,
    ) {}

    /** @return array<string, mixed>|null */
    public function forRun(string $runId): ?array
    {
        $run = $this->runs->find($runId);

        if ($run === null) {
            return null;
        }

        return [
            'run_id' => $runId,
            'trace' => [
                'trace_id' => $run['trace_id'] ?? null,
                'span_id' => $run['span_id'] ?? null,
            ],
            'audit' => $this->auditExporter->export($runId),
            'usage' => $this->usageReader->recordsForRun($runId),
        ];
    }
}
