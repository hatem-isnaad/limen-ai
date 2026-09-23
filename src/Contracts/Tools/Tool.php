<?php

namespace LimenAi\Contracts\Tools;

use LimenAi\Contracts\Runtime\ToolExecutionContext;

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
