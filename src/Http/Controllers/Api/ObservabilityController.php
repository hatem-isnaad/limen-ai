<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Http\Services\ConversationAccessGuard;
use LimenAi\Observability\RunObservabilityReporter;

class ObservabilityController
{
    public function __construct(
        private readonly RunRepository $runs,
        private readonly RunObservabilityReporter $reporter,
        private readonly ConversationAccessGuard $accessGuard,
    ) {}

    public function show(Request $request, string $runId): JsonResponse
    {
        $run = $this->runs->find($runId);
        abort_if($run === null, 404, 'Run not found.');
        abort_unless($this->accessGuard->canAccess($request->user(), (string) ($run['conversation_id'] ?? '')), 403, 'Run access denied.');
        $report = $this->reporter->forRun($runId);
        abort_if($report === null, 404, 'Run not found.');
        return response()->json(['observability' => $report]);
    }
}
