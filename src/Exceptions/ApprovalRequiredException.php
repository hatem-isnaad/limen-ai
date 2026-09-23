<?php

namespace LimenAi\Exceptions;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;

class ApprovalRequiredException extends ToolException
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(
        string $message,
        private readonly ToolDefinition $tool,
        private readonly array $input,
        private readonly ToolExecutionContext $context,
    ) {
        parent::__construct($message);
    }

    public function tool(): ToolDefinition
    {
        return $this->tool;
    }

    /** @return array<string, mixed> */
    public function input(): array
    {
        return $this->input;
    }

    public function context(): ToolExecutionContext
    {
        return $this->context;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function forTool(ToolDefinition $tool, array $input, ToolExecutionContext $context): self
    {
        return new self(
            "Tool [{$tool->key()}] requires approval before execution.",
            $tool,
            $input,
            $context,
        );
    }
}
