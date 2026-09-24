<?php

namespace LimenAi\Authorization;

use Illuminate\Support\Str;
use LimenAi\Contracts\Authorization\ApprovalRepository;

class InMemoryApprovalRepository implements ApprovalRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $approvals = [];

    public function request(string $runId, string $toolKey, array $payload, ?int $requestedBy = null): string
    {
        $approvalId = (string) Str::uuid();

        $this->approvals[$approvalId] = [
            'id' => $approvalId,
            'run_id' => $runId,
            'tool_key' => $toolKey,
            'payload' => $payload,
            'status' => ApprovalStatus::PENDING,
            'requested_by' => $requestedBy,
            'resolved_by' => null,
            'resolved_at' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

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
        return $this->approvals[$approvalId] ?? null;
    }

    public function findPendingForRun(string $runId): ?array
    {
        foreach ($this->approvals as $approval) {
            if ($approval['run_id'] === $runId && $approval['status'] === ApprovalStatus::PENDING) {
                return $approval;
            }
        }

        return null;
    }

    protected function resolve(string $approvalId, int $userId, string $status): void
    {
        if (! isset($this->approvals[$approvalId])) {
            return;
        }

        $this->approvals[$approvalId] = array_merge($this->approvals[$approvalId], [
            'status' => $status,
            'resolved_by' => $userId,
            'resolved_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
