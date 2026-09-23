<?php

namespace LimenAi\Contracts\Runtime;

use LimenAi\Runtime\AgentRunDispatchResult;

interface AgentRunDispatcher
{
    public function dispatchRun(
        string $agentKey,
        string $conversationId,
        string $userMessage,
        RunContext $context,
    ): AgentRunDispatchResult;

    public function dispatchResume(string $runId, RunContext $context): AgentRunDispatchResult;

    public function dispatchCancel(string $runId, RunContext $context): AgentRunDispatchResult;

    public function dispatchReject(string $runId, RunContext $context): AgentRunDispatchResult;
}
