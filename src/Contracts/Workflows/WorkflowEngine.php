<?php

namespace LimenAi\Contracts\Workflows;

use LimenAi\Contracts\Runtime\RunContext;

interface WorkflowEngine
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function start(WorkflowDefinition $workflow, array $input, RunContext $context): string;

    public function resume(string $runId, RunContext $context): void;

    public function cancel(string $runId, RunContext $context): void;
}
