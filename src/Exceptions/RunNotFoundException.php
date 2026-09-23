<?php

namespace LimenAi\Exceptions;

class RunNotFoundException extends ToolException
{
    public static function forId(string $runId): self
    {
        return new self("Agent run [{$runId}] was not found.");
    }
}
