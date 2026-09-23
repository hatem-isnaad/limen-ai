<?php

namespace LimenAi\Runtime;

use Illuminate\Database\ConnectionInterface;
use LimenAi\Contracts\Runtime\CheckpointStore;

class DatabaseCheckpointStore implements CheckpointStore
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function save(string $runId, int $step, array $state): void
    {
        $payload = [
            'step' => $step,
            'state' => json_encode($state, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];

        $exists = $this->db->table('limen_ai_run_checkpoints')->where('run_id', $runId)->exists();

        if ($exists) {
            $this->db->table('limen_ai_run_checkpoints')->where('run_id', $runId)->update($payload);

            return;
        }

        $this->db->table('limen_ai_run_checkpoints')->insert(array_merge($payload, [
            'run_id' => $runId,
            'created_at' => now(),
        ]));
    }

    public function load(string $runId): ?array
    {
        $row = $this->db->table('limen_ai_run_checkpoints')->where('run_id', $runId)->first();

        if ($row === null) {
            return null;
        }

        $row = (array) $row;

        return [
            'step' => (int) $row['step'],
            'state' => json_decode((string) $row['state'], true, 512, JSON_THROW_ON_ERROR),
        ];
    }

    public function delete(string $runId): void
    {
        $this->db->table('limen_ai_run_checkpoints')->where('run_id', $runId)->delete();
    }
}
