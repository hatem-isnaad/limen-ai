<?php

namespace LimenAi\Contracts\Runtime;

interface AgentRuntime
{
    public function run(string $agentKey, string $conversationId, string $userMessage, RunContext $context): string;

    /**
     * @return \Generator<int, \LimenAi\Providers\LlmStreamChunk>
     */
    public function stream(string $agentKey, string $conversationId, string $userMessage, RunContext $context): \Generator;

    public function resume(string $runId, RunContext $context): void;

    public function cancel(string $runId, RunContext $context): void;

    public function reject(string $runId, RunContext $context): void;
}
