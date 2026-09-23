<?php

namespace LimenAi\Contracts\Tools;

use LimenAi\Contracts\Runtime\ToolExecutionContext;

/**
 * Optional tool contract for class-based authorization without Laravel Gates.
 *
 * Return false to block execution before handle() runs.
 */
interface AuthorizableTool extends Tool
{
    /**
     * @param  array<string, mixed>  $input  Validated tool input
     */
    public function authorize(array $input, ToolExecutionContext $context): bool;
}
