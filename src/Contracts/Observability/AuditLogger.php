<?php

namespace LimenAi\Contracts\Observability;

interface AuditLogger
{
    public function log(string $action, array $context): void;
}
