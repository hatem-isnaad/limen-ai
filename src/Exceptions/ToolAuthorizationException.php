<?php

namespace LimenAi\Exceptions;

class ToolAuthorizationException extends ToolException
{
    public static function forTool(string $toolKey): self
    {
        return new self("You are not authorized to use tool [{$toolKey}].");
    }
}
