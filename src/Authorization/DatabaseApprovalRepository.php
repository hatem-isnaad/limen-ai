<?php

namespace LimenAi\Authorization;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use LimenAi\Contracts\Authorization\ApprovalRepository;

class DatabaseApprovalRepository implements ApprovalRepository
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function request(string $runId, string $toolKey, array $payload, ?int $requestedBy = null): string
    {
        $approvalId = (string) Str::uuid();
        $now = now();
        $this->db->table('limen_ai_approvals')->insert([
            'id' => $approvalId, 'run_id' => $runId, 'tool_key' => $toolKey,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => ApprovalStatus::PENDING, 'requested_by' => $requestedBy,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return $approvalId;
    }

    public function approve(string $approvalId, int $userId): void
    {
        $this->resolve($approvalId, $userId, ApprovalStatus::APPROVED);
    }

    public function reject(string $approvalId, int $userId): void
    {
        $this->resolve($approvalId, $userId, ApprovalStatus::REJECTED);
    }

    public function find(string $approvalId): ?array
    {
        $row = $this->db->table('limen_ai_approvals')->where('id', $approvalId)->first();

        return $row === null ? null : $this->mapRow((array) $row);
    }

    public function findPendingForRun(string $runId): ?array
    {
        $row = $this->db->table('limen_ai_approvals')->where('run_id', $runId)->where('status', ApprovalStatus::PENDING)->orderBy('created_at')->first();

        return $row === null ? null : $this->mapRow((array) $row);
    }

    protected function resolve(string $approvalId, int $userId, string $status): void
    {
        $this->db->table('limen_ai_approvals')->where('id', $approvalId)->update([
            'status' => $status, 'resolved_by' => $userId, 'resolved_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function mapRow(array $row): array
    {
        return [
            'id' => (string) $row['id'], 'run_id' => (string) $row['run_id'], 'tool_key' => (string) $row['tool_key'],
            'payload' => json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR),
            'status' => (string) $row['status'],
            'requested_by' => isset($row['requested_by']) ? (int) $row['requested_by'] : null,
            'resolved_by' => isset($row['resolved_by']) ? (int) $row['resolved_by'] : null,
            'resolved_at' => $row['resolved_at'] ?? null,
            'created_at' => $row['created_at'] ?? null, 'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
