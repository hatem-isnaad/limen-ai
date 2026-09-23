<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\RunContext;

class WorkflowFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $runId,
        public readonly string $workflowKey,
        public readonly string $error,
        public readonly RunContext $context,
    ) {}
}
