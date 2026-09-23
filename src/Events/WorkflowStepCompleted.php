<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\RunContext;

class WorkflowStepCompleted
{
    use Dispatchable;
    use SerializesModels;

    /** @param  array<string, mixed>  $output */
    public function __construct(
        public readonly string $runId,
        public readonly string $workflowKey,
        public readonly string $stepKey,
        public readonly array $output,
        public readonly RunContext $context,
    ) {}
}
