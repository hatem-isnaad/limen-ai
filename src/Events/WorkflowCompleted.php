<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\RunContext;

class WorkflowCompleted
{
    use Dispatchable;
    use SerializesModels;

    /** @param  array<string, mixed>  $stepOutputs */
    public function __construct(
        public readonly string $runId,
        public readonly string $workflowKey,
        public readonly array $stepOutputs,
        public readonly RunContext $context,
    ) {}
}
