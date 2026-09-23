<?php

namespace LimenAi\Contracts\Runtime;

interface RunStatusReader
{
    /** @return array<string, mixed>|null */
    public function find(string $runId): ?array;

    public function isTerminal(string $runId): bool;
}
