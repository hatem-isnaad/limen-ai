<?php

namespace LimenAi\Contracts\Workflows;

interface WorkflowDefinition
{
    public function key(): string;

    public function name(): string;

    /** @return array<string, mixed> */
    public function definition(): array;

    public function version(): string;
}
