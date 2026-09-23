<?php

namespace LimenAi\Contracts\Tools;

use LimenAi\Contracts\Runtime\ToolExecutionContext;

interface ToolExecutor
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(ToolDefinition $tool, array $input, ToolExecutionContext $context): array;
}
