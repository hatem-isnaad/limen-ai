<?php

namespace LimenAi\Runtime;

use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunContext;

class SyncAgentRunDispatcher implements AgentRunDispatcher
{
    public function __construct(
        private readonly AgentRuntime $runtime,
    ) {}

    public function dispatchRun(
        string $agentKey,
        string $conversationId,
        string $userMessage,
        RunContext $context,
    ): AgentRunDispatchResult {
        $runId = $this->runtime->run($agentKey, $conversationId, $userMessage, $context);

        return new AgentRunDispatchResult(queued: false, runId: $runId);
    }

    public function dispatchResume(string $runId, RunContext $context): AgentRunDispatchResult
    {
        $this->runtime->resume($runId, $context);

        return new AgentRunDispatchResult(queued: false, runId: $runId);
    }

    public function dispatchCancel(string $runId, RunContext $context): AgentRunDispatchResult
    {
        $this->runtime->cancel($runId, $context);

        return new AgentRunDispatchResult(queued: false, runId: $runId);
    }

    public function dispatchReject(string $runId, RunContext $context): AgentRunDispatchResult
    {
        $this->runtime->reject($runId, $context);

        return new AgentRunDispatchResult(queued: false, runId: $runId);
    }
}
