<?php

namespace LimenAi\Observability;

use LimenAi\Contracts\Observability\AuditExporter;

class DefaultAuditExporter implements AuditExporter
{
    public function __construct(
        private readonly AuditBuffer $buffer,
    ) {}

    /** @return list<array<string, mixed>> */
    public function export(?string $runId = null): array
    {
        if ($runId === null || $runId === '') {
            return $this->buffer->all();
        }

        return $this->buffer->forRun($runId);
    }
}
