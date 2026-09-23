<?php

namespace LimenAi\Contracts\Runtime;

interface CheckpointStore
{
    public function save(string $runId, int $step, array $state): void;

    public function load(string $runId): ?array;

    public function delete(string $runId): void;
}
