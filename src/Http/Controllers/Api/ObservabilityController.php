<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Http\Concerns\AuthorizesConversationAccess;
use LimenAi\Observability\RunObservabilityReporter;

class ObservabilityController
{
    use AuthorizesConversationAccess;

    public function __construct(
        private readonly RunRepository $runs,
        private readonly RunObservabilityReporter $reporter,
    ) {}

    public function show(Request $request, string $runId): JsonResponse
    {
        $run = $this->runs->find($runId);
        abort_if($run === null, 404, 'Run not found.');
        $this->authorizeConversationAccess($request, (string) ($run['conversation_id'] ?? ''));
        $report = $this->reporter->forRun($runId);
        abort_if($report === null, 404, 'Run not found.');
        return response()->json(['observability' => $report]);
    }
}
