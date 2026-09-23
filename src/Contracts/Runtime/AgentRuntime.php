<?php

namespace LimenAi\Contracts\Runtime;

interface AgentRuntime
{
    public function run(string $agentKey, string $conversationId, string $userMessage, RunContext $context): string;

    public function resume(string $runId, RunContext $context): void;

    public function cancel(string $runId, RunContext $context): void;
}
