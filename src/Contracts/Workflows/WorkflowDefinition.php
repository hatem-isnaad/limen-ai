<?php

namespace LimenAi\Contracts\Workflows;

use LimenAi\Contracts\Enableable;

interface WorkflowDefinition extends Enableable
{
    public function key(): string;

    public function name(): string;

    /** @return array<string, mixed> */
    public function definition(): array;

    public function startStep(): string;

    /** @return array<string, array<string, mixed>> */
    public function steps(): array;

    public function version(): string;
}
