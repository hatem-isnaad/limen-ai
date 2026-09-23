<?php

namespace LimenAi\Contracts\Runtime;

interface ToolExecutionContext extends RunContext
{
    public function runId(): string;

    public function conversationId(): string;

    public function agentKey(): string;
}
