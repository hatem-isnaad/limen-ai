<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Tools\IdempotencyGuard;

class NullIdempotencyGuard implements IdempotencyGuard
{
    public function has(string $key): bool
    {
        return false;
    }

    public function get(string $key): ?array
    {
        return null;
    }

    public function remember(string $key, array $result): void
    {
        // Intentionally noop.
    }
}
