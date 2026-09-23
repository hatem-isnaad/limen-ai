<?php

namespace LimenAi\Tests\Unit\Authorization;

use LimenAi\Authorization\ApprovalStatus;
use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Tests\TestCase;

class InMemoryApprovalRepositoryTest extends TestCase
{
    public function test_it_creates_and_resolves_pending_approvals(): void
    {
        $repository = app(ApprovalRepository::class);

        $approvalId = $repository->request('run-1', 'confirmation_tool', [
            'message' => 'send it',
        ], 1);

        $pending = $repository->findPendingForRun('run-1');

        $this->assertNotNull($pending);
        $this->assertSame($approvalId, $pending['id']);
        $this->assertSame(ApprovalStatus::PENDING, $pending['status']);

        $repository->approve($approvalId, 2);

        $this->assertNull($repository->findPendingForRun('run-1'));
        $this->assertSame(ApprovalStatus::APPROVED, $repository->find($approvalId)['status']);
    }

    public function test_it_rejects_pending_approvals(): void
    {
        $repository = new InMemoryApprovalRepository;
        $approvalId = $repository->request('run-2', 'confirmation_tool', ['message' => 'no']);

        $repository->reject($approvalId, 3);

        $this->assertSame(ApprovalStatus::REJECTED, $repository->find($approvalId)['status']);
    }
}
