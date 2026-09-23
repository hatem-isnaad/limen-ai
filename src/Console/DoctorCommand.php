<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\AgentValidator;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Support\LimenAiManager;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Workflows\WorkflowValidator;

class DoctorCommand extends Command
{
    protected $signature = 'limen-ai:doctor {--json : Output the report as JSON for CI and scripts}';

    protected $description = 'Validate Limen AI environment, bindings, and configuration';

    public function handle(
        AgentValidator $agents,
        WorkflowValidator $workflows,
        HttpIntegrationValidator $integrations,
        EnvironmentDoctor $environmentDoctor,
    ): int {
        $failures = [];
        $checks = [];
        $environment = (string) app()->environment();

        if (! config()->has('limen-ai')) {
            $failures[] = 'Configuration file [config/limen-ai.php] is missing.';
            $checks['configuration'] = 'missing';
        } else {
            $checks['configuration'] = 'ok';
        }

        $defaultAgent = (string) config('limen-ai.default_agent', '');

        if ($defaultAgent === '') {
            $failures[] = 'Default agent is not configured.';
            $checks['default_agent'] = 'missing';
        } elseif ($agents->validate($defaultAgent) !== []) {
            $failures[] = "Default agent [{$defaultAgent}] failed validation.";
            $checks['default_agent'] = 'invalid';
        } else {
            $checks['default_agent'] = $defaultAgent;
        }

        $bindings = [];

        foreach ([
            'agent_runtime' => AgentRuntime::class,
            'agent_dispatcher' => AgentRunDispatcher::class,
            'llm_provider' => LlmProvider::class,
            'audit_logger' => AuditLogger::class,
            'broadcaster' => RealtimeBroadcaster::class,
            'agent_repository' => AgentRepository::class,
            'attachment_store' => AttachmentStore::class,
            'limen_ai_manager' => LimenAiManager::class,
        ] as $key => $contract) {
            if (! app()->bound($contract)) {
                $failures[] = "Binding missing for {$contract}.";
                $bindings[$key] = 'missing';
                continue;
            }

            $bindings[$key] = 'bound';
        }

        $checks['bindings'] = $bindings;

        $queueEnabled = (bool) config('limen-ai.queue.agent_runs', false);
        $checks['queue_agent_runs'] = $queueEnabled ? 'enabled' : 'disabled';

        if ($queueEnabled && config('limen-ai.queue.connection') === null) {
            $checks['queue_agent_runs'] = 'enabled (default connection)';
        }

        $broadcastDriver = (string) config('limen-ai.broadcasting.driver', 'null');
        $checks['broadcast_driver'] = $broadcastDriver;

        if ($broadcastDriver === 'pusher' && config('broadcasting.default') === null) {
            $failures[] = 'Pusher broadcast driver selected but Laravel broadcasting is not configured.';
        }

        $validationErrors = array_merge(
            $agents->validateAll(),
            $workflows->validateAll(),
            $integrations->validateAll(),
        );

        if ($validationErrors !== []) {
            $failures = array_merge($failures, $validationErrors);
            $checks['definitions'] = 'invalid';
        } else {
            $checks['definitions'] = 'valid';
        }

        $environmentReport = $environmentDoctor->inspect($environment);
        $failures = array_merge($failures, $environmentReport['failures']);
        $warnings = $environmentReport['warnings'];

        $report = [
            'ok' => $failures === [],
            'healthy' => $failures === [],
            'environment' => $environment,
            'checks' => $checks,
            'failures' => $failures,
            'warnings' => $warnings,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

            return $failures === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info('Running Limen AI environment checks...');

        if (($checks['configuration'] ?? null) === 'ok') {
            $this->components->twoColumnDetail('Configuration', '<fg=green>OK</>');
        }

        if (isset($checks['default_agent']) && ! in_array($checks['default_agent'], ['missing', 'invalid'], true)) {
            $this->components->twoColumnDetail('Default agent', "<fg=green>{$checks['default_agent']}</>");
        }

        foreach ([
            'Agent runtime' => 'agent_runtime',
            'Agent dispatcher' => 'agent_dispatcher',
            'LLM provider' => 'llm_provider',
            'Audit logger' => 'audit_logger',
            'Broadcaster' => 'broadcaster',
            'Agent repository' => 'agent_repository',
            'Attachment store' => 'attachment_store',
            'Limen AI manager' => 'limen_ai_manager',
        ] as $label => $key) {
            if (($bindings[$key] ?? null) === 'bound') {
                $this->components->twoColumnDetail($label, '<fg=green>bound</>');
            }
        }

        $this->components->twoColumnDetail('Queue agent runs', $checks['queue_agent_runs']);
        $this->components->twoColumnDetail('Broadcast driver', $broadcastDriver);

        if (($checks['definitions'] ?? null) === 'valid') {
            $this->components->twoColumnDetail('Definitions', '<fg=green>valid</>');
        }

        if ($warnings !== []) {
            $this->newLine();
            $this->components->warn('Limen AI doctor warnings:');

            foreach ($warnings as $warning) {
                $this->line("- {$warning}");
            }
        }

        if ($failures !== []) {
            $this->newLine();
            $this->components->error('Limen AI doctor found problems:');

            foreach ($failures as $failure) {
                $this->line("- {$failure}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Limen AI environment looks healthy.');

        return self::SUCCESS;
    }
}
