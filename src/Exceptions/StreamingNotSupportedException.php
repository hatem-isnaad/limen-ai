<?php

namespace LimenAi\Exceptions;

use RuntimeException;

final class StreamingNotSupportedException extends RuntimeException
{
    public static function forReason(string $reason): self
    {
        return new self($reason);
    }
}
