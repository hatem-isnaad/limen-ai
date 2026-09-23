<?php

namespace LimenAi\Exceptions;

class RunContextAuthorizationException extends ToolException
{
    public static function userMismatch(int $contextUserId, int $authenticatedUserId): self
    {
        return new self(
            "Run context user [{$contextUserId}] does not match authenticated user [{$authenticatedUserId}].",
        );
    }
}
