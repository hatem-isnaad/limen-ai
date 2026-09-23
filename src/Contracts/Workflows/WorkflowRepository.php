<?php

namespace LimenAi\Contracts\Workflows;

interface WorkflowRepository
{
    public function find(string $key): ?WorkflowDefinition;

    /** @return list<WorkflowDefinition> */
    public function all(): array;
}
