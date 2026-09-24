<?php

namespace LimenAi\Exceptions;

class AgentDisabledException extends ToolException
{
    public static function forAgent(string $agentKey): self
    {
        return new self("Agent [{$agentKey}] is disabled.");
    }
}
