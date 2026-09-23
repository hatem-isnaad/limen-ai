<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\AgentValidator;
use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Workflows\WorkflowValidator;

class ValidateCommand extends Command
{
    protected $signature = 'limen-ai:validate {agent? : Optional agent key to validate}';

    protected $description = 'Validate Limen AI agent, tool, and skill configuration';

    public function handle(
        AgentValidator $validator,
        WorkflowValidator $workflows,
        HttpIntegrationValidator $integrations,
    ): int {
        $agentKey = $this->argument('agent');

        $errors = $agentKey
            ? $validator->validate((string) $agentKey)
            : array_merge(
                $validator->validateAll(),
                $workflows->validateAll(),
                $integrations->validateAll(),
            );

        $warnings = $agentKey
            ? $validator->warnings((string) $agentKey)
            : $validator->warningsAll();

        if ($errors === []) {
            $this->components->info($agentKey
                ? "Agent [{$agentKey}] configuration is valid."
                : 'All Limen AI agent and workflow configurations are valid.');

            foreach ($warnings as $warning) {
                $this->components->warn($warning);
            }

            return self::SUCCESS;
        }

        $this->components->error($agentKey
            ? "Agent [{$agentKey}] configuration is invalid."
            : 'Limen AI configuration validation failed.');

        foreach ($errors as $error) {
            $this->line("- {$error}");
        }

        return self::FAILURE;
    }
}
