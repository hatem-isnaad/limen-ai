<?php

namespace LimenAi\Runtime;

use Illuminate\Support\Str;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Exceptions\RunNotFoundException;

class InMemoryRunRepository implements RunRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $runs = [];

    public function create(array $attributes): string
    {
        $runId = (string) ($attributes['id'] ?? Str::uuid());

        $this->runs[$runId] = array_merge([
            'id' => $runId,
            'status' => RunStatus::PENDING,
            'current_step' => 0,
            'tool_call_count' => 0,
            'messages' => [],
            'final_message' => null,
            'error' => null,
            'metadata' => [],
        ], $attributes);

        return $runId;
    }

    public function find(string $runId): ?array
    {
        return $this->runs[$runId] ?? null;
    }

    public function updateStatus(string $runId, string $status, ?array $metadata = null): void
    {
        $run = $this->find($runId);

        if ($run === null) {
            throw RunNotFoundException::forId($runId);
        }

        $run['status'] = $status;

        if ($metadata !== null) {
            $run = array_merge($run, $metadata);
        }

        $this->runs[$runId] = $run;
    }

}
