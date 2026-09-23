<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\AuthorizableTool;
use LimenAi\Contracts\Tools\ToolDefinition;

/**
 * Host tool base class — override authorize() and handle() only.
 */
abstract class BaseTool implements AuthorizableTool
{
    abstract public function key(): string;

    abstract public function definition(): ToolDefinition;

    /**
     * @param  array<string, mixed>  $input
     */
    public function authorize(array $input, ToolExecutionContext $context): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    abstract public function handle(array $input, ToolExecutionContext $context): array;
}
