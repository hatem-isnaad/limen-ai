<?php

namespace LimenAi\Contracts\Runtime;

use LimenAi\Agents\ResolvedAgent;
use LimenAi\Runtime\PendingAgentStep;

interface AgentStepMiddleware
{
    public function handle(PendingAgentStep $step, callable $next): PendingAgentStep;
}
