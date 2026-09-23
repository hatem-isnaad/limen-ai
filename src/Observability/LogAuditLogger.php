<?php

namespace LimenAi\Observability;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Log;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Security\SensitiveDataRedactor;

class LogAuditLogger implements AuditLogger
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly SensitiveDataRedactor $redactor,
    ) {}

    public function log(string $action, array $context): void
    {
        if (! $this->config->get('limen-ai.observability.audit_enabled', true)) {
            return;
        }

        Log::info('[limen-ai] '.$action, $this->redactor->redact($context));
    }
}
