<?php

namespace LimenAi\Runtime;

use LimenAi\Contracts\Runtime\CheckpointStore;

class ArrayCheckpointStore implements CheckpointStore
{
    /** @var array<string, array<string, mixed>> */
    private array $checkpoints = [];

    public function save(string $runId, int $step, array $state): void
    {
        $this->checkpoints[$runId] = [
            'step' => $step,
            'state' => $state,
        ];
    }

    public function load(string $runId): ?array
    {
        return $this->checkpoints[$runId] ?? null;
    }

    public function delete(string $runId): void
    {
        unset($this->checkpoints[$runId]);
    }
}
