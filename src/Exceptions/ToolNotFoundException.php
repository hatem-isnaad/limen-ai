<?php

namespace LimenAi\Exceptions;

class ToolNotFoundException extends ToolException
{
    public static function forKey(string $toolKey): self
    {
        return new self("Tool [{$toolKey}] was not found.");
    }
}
