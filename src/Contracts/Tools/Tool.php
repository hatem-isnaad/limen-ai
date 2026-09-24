<?php

namespace LimenAi\Contracts\Tools;

use LimenAi\Contracts\Enableable;
use LimenAi\Contracts\Runtime\ToolExecutionContext;

/**
 * Tool executors may also implement {@see Enableable} for runtime enable/disable checks.
 */
interface Tool
{
    public function key(): string;

    public function definition(): ToolDefinition;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(array $input, ToolExecutionContext $context): array;
}
