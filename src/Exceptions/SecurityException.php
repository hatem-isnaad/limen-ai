<?php

namespace LimenAi\Exceptions;

class SecurityException extends ToolException
{
    public static function ssrfBlocked(string $url, string $reason): self
    {
        return new self("Outbound URL blocked by SSRF policy ({$reason}): {$url}");
    }
}
