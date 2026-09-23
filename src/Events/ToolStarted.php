<?php

namespace LimenAi\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;

class ToolStarted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(
        public readonly string $executionId,
        public readonly ToolDefinition $tool,
        public readonly array $input,
        public readonly ToolExecutionContext $context,
    ) {}
}
