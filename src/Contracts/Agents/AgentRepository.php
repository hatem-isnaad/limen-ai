<?php

namespace LimenAi\Contracts\Agents;

interface AgentRepository
{
    public function find(string $key): ?AgentDefinition;

    /** @return list<AgentDefinition> */
    public function all(): array;

    public function exists(string $key): bool;
}
