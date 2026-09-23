<?php

namespace LimenAi\Exceptions;

class MemoryPolicyException extends \RuntimeException
{
    public static function forKey(string $key, string $reason): self
    {
        return new self("Memory key [{$key}] rejected: {$reason}");
    }
}
