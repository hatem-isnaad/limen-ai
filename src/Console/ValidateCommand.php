<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use LimenAi\Agents\AgentValidator;
use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Support\PersistenceConfig;
use LimenAi\Workflows\WorkflowValidator;

class ValidateCommand extends Command
{
    protected $signature = 'limen-ai:validate
                            {agent? : Optional agent key to validate}
                            {--strict : Fail when tool-count critical warnings are present}';

    protected $description = 'Validate Limen AI agent, tool, and skill configuration';

    public function handle(
        AgentValidator $validator,
        WorkflowValidator $workflows,
        HttpIntegrationValidator $integrations,
        ConnectionResolverInterface $database,
    ): int {
        $agentKey = $this->argument('agent');
        $this->reportPersistenceMode($database);

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

        if ($this->option('strict')) {
            $criticalWarnings = array_values(array_filter(
                $warnings,
                fn (string $warning): bool => str_contains($warning, '(critical)'),
            ));

            if ($criticalWarnings !== []) {
                $errors = array_merge($errors, $criticalWarnings);
                $warnings = array_values(array_filter(
                    $warnings,
                    fn (string $warning): bool => ! str_contains($warning, '(critical)'),
                ));
            }
        }

        if ($warnings !== []) {
            $this->components->warn('Validation warnings:');

            foreach ($warnings as $warning) {
                $this->line("- {$warning}");
            }
        }

        if ($errors === []) {
            $this->components->info($agentKey
                ? "Agent [{$agentKey}] configuration is valid."
                : 'All Limen AI agent and workflow configurations are valid.');

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

    protected function reportPersistenceMode(ConnectionResolverInterface $database): void
    {
        $tableExists = false;

        try {
            $tableExists = $database->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations');
        } catch (\Throwable) {
            $tableExists = false;
        }

        $configured = config('limen-ai.persistence.driver');
        $driver = PersistenceConfig::resolveDriver(
            is_string($configured) ? $configured : null,
            (bool) config('limen-ai.persistence.auto_detect', true),
            $tableExists,
        );

        $mode = is_string($configured) && $configured !== ''
            ? $configured
            : ($tableExists ? 'auto-detected:'.$driver : 'unset');

        $this->components->twoColumnDetail('Persistence mode', $mode);
    }
}
