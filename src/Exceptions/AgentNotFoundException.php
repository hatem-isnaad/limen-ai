<?php

namespace LimenAi\Exceptions;

class AgentNotFoundException extends AgentConfigurationException
{
    public static function forKey(string $agentKey): self
    {
        return new self("Limen AI agent [{$agentKey}] was not found.");
    }
}
