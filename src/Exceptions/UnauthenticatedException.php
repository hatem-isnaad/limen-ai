<?php

namespace LimenAi\Exceptions;

class UnauthenticatedException extends ToolException
{
    public static function forAgent(string $agentKey): self
    {
        return new self("Authentication is required to use agent [{$agentKey}].");
    }

    public static function guestSessionRequired(string $agentKey): self
    {
        return new self("A valid guest session is required to use agent [{$agentKey}].");
    }

    public static function guestSessionInvalid(string $agentKey): self
    {
        return new self("The guest session for agent [{$agentKey}] is invalid or expired.");
    }
}
