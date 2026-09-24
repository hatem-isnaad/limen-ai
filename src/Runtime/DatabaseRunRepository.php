<?php

namespace LimenAi\Runtime;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Exceptions\RunNotFoundException;

class DatabaseRunRepository implements RunRepository
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function create(array $attributes): string
    {
        $runId = (string) ($attributes['id'] ?? Str::uuid());
        $now = now();

        $this->db->table('limen_ai_runs')->insert([
            'id' => $runId,
            'conversation_id' => (string) ($attributes['conversation_id'] ?? ''),
            'agent_key' => (string) ($attributes['agent_key'] ?? ''),
            'user_id' => $attributes['user_id'] ?? null,
            'status' => (string) ($attributes['status'] ?? RunStatus::PENDING),
            'current_step' => (int) ($attributes['current_step'] ?? 0),
            'tool_call_count' => (int) ($attributes['tool_call_count'] ?? 0),
            'final_message' => $attributes['final_message'] ?? null,
            'error' => $attributes['error'] ?? null,
            'messages' => isset($attributes['messages']) ? json_encode($attributes['messages'], JSON_THROW_ON_ERROR) : null,
            'metadata' => isset($attributes['metadata']) ? json_encode($attributes['metadata'], JSON_THROW_ON_ERROR) : null,
            'started_at' => $attributes['status'] === RunStatus::RUNNING ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $runId;
    }

    public function find(string $runId): ?array
    {
        $row = $this->db->table('limen_ai_runs')->where('id', $runId)->first();

        return $row === null ? null : $this->mapRow((array) $row);
    }

    public function updateStatus(string $runId, string $status, ?array $metadata = null): void
    {
        if ($this->find($runId) === null) {
            throw RunNotFoundException::forId($runId);
        }

        $updates = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($metadata !== null) {
            foreach (['current_step', 'tool_call_count', 'final_message', 'error', 'user_id'] as $field) {
                if (array_key_exists($field, $metadata)) {
                    $updates[$field] = $metadata[$field];
                }
            }

            if (isset($metadata['messages'])) {
                $updates['messages'] = json_encode($metadata['messages'], JSON_THROW_ON_ERROR);
            }

            if (isset($metadata['metadata'])) {
                $updates['metadata'] = json_encode($metadata['metadata'], JSON_THROW_ON_ERROR);
            }

            $existing = $this->find($runId);
            $mergedMeta = is_array($existing['metadata'] ?? null) ? $existing['metadata'] : [];

            foreach (['usage_summary', 'structured_output'] as $metaKey) {
                if (array_key_exists($metaKey, $metadata)) {
                    $mergedMeta[$metaKey] = $metadata[$metaKey];
                }
            }

            if ($mergedMeta !== []) {
                $updates['metadata'] = json_encode($mergedMeta, JSON_THROW_ON_ERROR);
            }
        }

        if (in_array($status, [RunStatus::COMPLETED, RunStatus::FAILED, RunStatus::CANCELLED], true)) {
            $updates['finished_at'] = now();
        }

        if ($status === RunStatus::RUNNING) {
            $updates['started_at'] = now();
        }

        $this->db->table('limen_ai_runs')->where('id', $runId)->update($updates);
    }

    /** @param  array<string, mixed>  $row */
    protected function mapRow(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'conversation_id' => (string) $row['conversation_id'],
            'agent_key' => (string) $row['agent_key'],
            'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : null,
            'status' => (string) $row['status'],
            'current_step' => (int) $row['current_step'],
            'tool_call_count' => (int) $row['tool_call_count'],
            'final_message' => $row['final_message'],
            'error' => $row['error'],
            'messages' => isset($row['messages']) ? json_decode((string) $row['messages'], true, 512, JSON_THROW_ON_ERROR) : [],
            'metadata' => isset($row['metadata']) ? json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR) : [],
        ];
    }
}
