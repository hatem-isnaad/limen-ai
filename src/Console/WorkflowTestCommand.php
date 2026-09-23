<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;

class WorkflowTestCommand extends Command
{
    protected $signature = 'limen-ai:workflow:test
                            {workflow : The workflow key to dry-run}
                            {--input={} : JSON workflow input payload}
                            {--user=1 : User id for the run context}';

    protected $description = 'Dry-run a workflow using the configured fake or live providers';

    public function handle(
        WorkflowRepository $workflows,
        WorkflowEngine $engine,
        RunRepository $runs,
    ): int {
        $workflowKey = (string) $this->argument('workflow');
        $workflow = $workflows->find($workflowKey);

        if ($workflow === null) {
            $this->components->error("Workflow [{$workflowKey}] is not registered.");

            return self::FAILURE;
        }

        $input = json_decode((string) $this->option('input'), true);

        if (! is_array($input)) {
            $this->components->error('The --input option must be valid JSON.');

            return self::FAILURE;
        }

        $runId = $engine->start(
            $workflow,
            $input,
            RunContextData::make(['user_id' => (int) $this->option('user')]),
        );

        $run = $runs->find($runId);

        if ($run === null) {
            $this->components->error('Workflow run was not persisted.');

            return self::FAILURE;
        }

        $this->components->info("Workflow [{$workflowKey}] run [{$runId}] finished with status [{$run['status']}].");

        if (($run['step_outputs'] ?? []) !== []) {
            $this->line(json_encode($run['step_outputs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return in_array($run['status'] ?? null, [RunStatus::COMPLETED, RunStatus::WAITING_APPROVAL], true)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
