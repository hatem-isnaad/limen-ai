<?php

namespace LimenAi\Contracts\Tools;

interface IdempotencyGuard
{
    public function has(string $key): bool;

    /** @return array<string, mixed>|null */
    public function get(string $key): ?array;

    /** @param  array<string, mixed>  $result */
    public function remember(string $key, array $result): void;
}
