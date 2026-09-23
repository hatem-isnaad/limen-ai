<?php

namespace LimenAi\Contracts\Observability;

interface AuditExporter
{
    /** @return list<array<string, mixed>> */
    public function export(?string $runId = null): array;
}
