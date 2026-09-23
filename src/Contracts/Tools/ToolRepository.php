<?php

namespace LimenAi\Contracts\Tools;

interface ToolRepository
{
    public function find(string $key): ?ToolDefinition;

    /** @return list<ToolDefinition> */
    public function all(): array;

    /** @return list<ToolDefinition> */
    public function forAgent(string $agentKey): array;
}
