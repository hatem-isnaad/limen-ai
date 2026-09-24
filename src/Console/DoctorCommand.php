<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\AgentValidator;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Tools\ToolRepository;
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
        ToolRepository $tools,
        AgentRepository $agentRepository,
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

        $provider = (string) config('limen-ai.providers.default', 'fake');
        $this->components->twoColumnDetail('Default provider', $provider);

        foreach ($this->missingProviderEnvVars($provider) as $envVar) {
            $failures[] = "Provider [{$provider}] requires env var [{$envVar}].";
        }

        foreach ($agentRepository->all() as $agent) {
            foreach ($tools->forAgent($agent->key()) as $tool) {
                if ($tool->httpIntegration() === [] && $tool->executorClass() === '') {
                    $failures[] = "Tool [{$tool->key()}] assigned to agent [{$agent->key()}] is missing an executor class.";
                }
            }
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

    protected function missingProviderEnvVars(string $provider): array
    {
        if ($provider === 'fake') {
            return [];
        }

        $required = match ($provider) {
            'openai' => ['OPENAI_API_KEY'],
            'openrouter' => ['OPENROUTER_API_KEY'],
            'anthropic' => ['ANTHROPIC_API_KEY'],
            'gemini' => ['GEMINI_API_KEY'],
            default => [],
        };

        return array_values(array_filter(
            $required,
            fn (string $envVar): bool => blank(env($envVar)),
        ));
    }
}
