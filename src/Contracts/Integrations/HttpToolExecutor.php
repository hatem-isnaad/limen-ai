<?php

namespace LimenAi\Contracts\Integrations;

use LimenAi\Contracts\Runtime\ToolExecutionContext;

interface HttpToolExecutor
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(array $toolConfig, array $input, ToolExecutionContext $context): array;
}
