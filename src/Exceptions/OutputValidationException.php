<?php

namespace LimenAi\Exceptions;

use RuntimeException;

class OutputValidationException extends RuntimeException
{
    public static function forAgent(string $agentKey, string $reason): self
    {
        return new self("Agent [{$agentKey}] produced invalid output: {$reason}");
    }
}
