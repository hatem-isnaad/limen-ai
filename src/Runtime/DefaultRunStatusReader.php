<?php

namespace LimenAi\Runtime;

use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\RunStatusReader;

class DefaultRunStatusReader implements RunStatusReader
{
    public function __construct(
        private readonly RunRepository $runs,
    ) {}

    /** @return array<string, mixed>|null */
    public function find(string $runId): ?array
    {
        return $this->runs->find($runId);
    }

    public function isTerminal(string $runId): bool
    {
        $run = $this->find($runId);

        if ($run === null) {
            return true;
        }

        return in_array($run['status'] ?? '', [
            RunStatus::COMPLETED,
            RunStatus::FAILED,
            RunStatus::CANCELLED,
        ], true);
    }
}
