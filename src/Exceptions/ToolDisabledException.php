<?php

namespace LimenAi\Exceptions;

class ToolDisabledException extends ToolException
{
    public static function forTool(string $toolKey): self
    {
        return new self("Tool [{$toolKey}] is disabled.");
    }
}
