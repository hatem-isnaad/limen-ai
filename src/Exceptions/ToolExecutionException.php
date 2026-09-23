<?php

namespace LimenAi\Exceptions;

use Throwable;

class ToolExecutionException extends ToolException
{
    public static function forTool(string $toolKey, Throwable $previous): self
    {
        return new self("Tool [{$toolKey}] execution failed: {$previous->getMessage()}", 0, $previous);
    }
}
