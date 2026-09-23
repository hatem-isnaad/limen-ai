<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Http\Concerns\AuthorizesConversationAccess;
use LimenAi\Http\Concerns\BuildsRunContext;

class ApprovalController
{
    use AuthorizesConversationAccess;
    use BuildsRunContext;

    public function __construct(
        private readonly ApprovalRepository $approvals,
        private readonly RunRepository $runs,
        private readonly AgentRunDispatcher $dispatcher,
        private readonly AuthorizationService $authorization,
    ) {}

    public function approve(Request $request, string $approvalId): JsonResponse
    {
        return $this->resolve($request, $approvalId, approve: true);
    }

    public function reject(Request $request, string $approvalId): JsonResponse
    {
        return $this->resolve($request, $approvalId, approve: false);
    }

    protected function resolve(Request $request, string $approvalId, bool $approve): JsonResponse
    {
        $approval = $this->approvals->find($approvalId);
        abort_if($approval === null, 404, 'Approval not found.');
        $runId = (string) ($approval['run_id'] ?? '');
        $run = $this->runs->find($runId) ?? [];
        $conversationId = (string) ($run['conversation_id'] ?? '');
        $this->authorizeConversationAccess($request, $conversationId);
        $context = $this->runContextFromRequest($request, $this->authorization);
        $result = $approve ? $this->dispatcher->dispatchResume($runId, $context) : $this->dispatcher->dispatchReject($runId, $context);

        return response()->json(['approval_id' => $approvalId, 'run_id' => $runId, 'action' => $approve ? 'approved' : 'rejected', 'queued' => $result->queued]);
    }
}
