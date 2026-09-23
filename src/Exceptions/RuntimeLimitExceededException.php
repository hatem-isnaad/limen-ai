<?php

namespace LimenAi\Exceptions;

class RuntimeLimitExceededException extends ToolException
{
    public static function forLimit(string $limit, int $value): self
    {
        return new self("Agent run exceeded limit [{$limit}] with value [{$value}].");
    }
}
