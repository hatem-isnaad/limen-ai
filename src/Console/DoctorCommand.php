<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\AgentValidator;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Support\LimenAiManager;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Workflows\WorkflowValidator;

class DoctorCommand extends Command
{
    protected $signature = 'limen-ai:doctor';

    protected $description = 'Validate Limen AI environment, bindings, and configuration';

    public function handle(
        AgentValidator $agents,
        WorkflowValidator $workflows,
        HttpIntegrationValidator $integrations,
    ): int {
        $failures = [];

        $this->components->info('Running Limen AI environment checks...');

        if (! config()->has('limen-ai')) {
            $failures[] = 'Configuration file [config/limen-ai.php] is missing.';
        } else {
            $this->components->twoColumnDetail('Configuration', '<fg=green>OK</>');
        }

        $defaultAgent = (string) config('limen-ai.default_agent', '');

        if ($defaultAgent === '') {
            $failures[] = 'Default agent is not configured.';
        } elseif ($agents->validate($defaultAgent) !== []) {
            $failures[] = "Default agent [{$defaultAgent}] failed validation.";
        } else {
            $this->components->twoColumnDetail('Default agent', "<fg=green>{$defaultAgent}</>");
        }

        foreach ([
            'Agent runtime' => AgentRuntime::class,
            'Agent dispatcher' => AgentRunDispatcher::class,
            'LLM provider' => LlmProvider::class,
            'Audit logger' => AuditLogger::class,
            'Broadcaster' => RealtimeBroadcaster::class,
            'Agent repository' => AgentRepository::class,
            'Attachment store' => AttachmentStore::class,
            'Limen AI manager' => LimenAiManager::class,
        ] as $label => $contract) {
            if (! app()->bound($contract)) {
                $failures[] = "Binding missing for {$contract}.";
                continue;
            }

            $this->components->twoColumnDetail($label, '<fg=green>bound</>');
        }

        if ((bool) config('limen-ai.queue.agent_runs', false) && config('limen-ai.queue.connection') === null) {
            $this->components->twoColumnDetail('Queue agent runs', '<fg=yellow>enabled (default connection)</>');
        } else {
            $this->components->twoColumnDetail('Queue agent runs', (bool) config('limen-ai.queue.agent_runs', false) ? 'enabled' : 'disabled');
        }

        $broadcastDriver = (string) config('limen-ai.broadcasting.driver', 'null');
        $this->components->twoColumnDetail('Broadcast driver', $broadcastDriver);

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
        } else {
            $this->components->twoColumnDetail('Definitions', '<fg=green>valid</>');
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
