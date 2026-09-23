<?php

namespace LimenAi\Contracts\Runtime;

interface RunRepository
{
    public function create(array $attributes): string;

    public function find(string $runId): ?array;

    public function updateStatus(string $runId, string $status, ?array $metadata = null): void;
}
