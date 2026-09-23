<?php

namespace LimenAi\Contracts\Agents;

use LimenAi\Agents\ResolvedAgent;

interface AgentResolver
{
    public function resolve(string $agentKey): ResolvedAgent;

    public function exists(string $agentKey): bool;
}
