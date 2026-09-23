<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\RunStatusReader;
use LimenAi\Http\Concerns\AuthorizesConversationAccess;

class RunController
{
    use AuthorizesConversationAccess;

    public function __construct(
        private readonly RunRepository $runs,
        private readonly RunStatusReader $statusReader,
    ) {}

    public function show(Request $request, string $runId): JsonResponse
    {
        $run = $this->runs->find($runId);
        abort_if($run === null, 404, 'Run not found.');
        $this->authorizeConversationAccess($request, (string) ($run['conversation_id'] ?? ''));
        return response()->json(['run' => ['id' => $runId, 'status' => (string) ($run['status'] ?? ''), 'agent_key' => (string) ($run['agent_key'] ?? ''), 'conversation_id' => (string) ($run['conversation_id'] ?? ''), 'final_message' => $run['final_message'] ?? null, 'error' => $run['error'] ?? null, 'trace_id' => $run['trace_id'] ?? null, 'span_id' => $run['span_id'] ?? null, 'terminal' => $this->statusReader->isTerminal($runId)]]);
    }
}
