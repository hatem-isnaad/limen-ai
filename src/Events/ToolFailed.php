<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;

class ToolFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $executionId,
        public readonly ToolDefinition $tool,
        public readonly string $error,
        public readonly ToolExecutionContext $context,
    ) {}
}
