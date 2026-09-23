<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;

class ToolCompleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $output
     */
    public function __construct(
        public readonly string $executionId,
        public readonly ToolDefinition $tool,
        public readonly array $output,
        public readonly int $durationMs,
        public readonly ToolExecutionContext $context,
    ) {}
}
