<?php

namespace LimenAi\Exceptions;

class AgentAuthorizationException extends ToolException
{
    public static function forAgent(string $agentKey): self
    {
        return new self("You are not authorized to use agent [{$agentKey}].");
    }
}
