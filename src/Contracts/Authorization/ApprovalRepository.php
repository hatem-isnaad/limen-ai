<?php

namespace LimenAi\Contracts\Authorization;

interface ApprovalRepository
{
    public function request(string $runId, string $toolKey, array $payload, ?int $requestedBy = null): string;

    public function approve(string $approvalId, int $userId): void;

    public function reject(string $approvalId, int $userId): void;

    public function find(string $approvalId): ?array;

    public function findPendingForRun(string $runId): ?array;
}
