<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\AgentValidator;

class ValidateCommand extends Command
{
    protected $signature = 'limen-ai:validate {agent? : Optional agent key to validate}';

    protected $description = 'Validate Limen AI agent, tool, and skill configuration';

    public function handle(AgentValidator $validator): int
    {
        $agentKey = $this->argument('agent');

        $errors = $agentKey
            ? $validator->validate((string) $agentKey)
            : $validator->validateAll();

        if ($errors === []) {
            $this->components->info($agentKey
                ? "Agent [{$agentKey}] configuration is valid."
                : 'All Limen AI agent configurations are valid.');

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
