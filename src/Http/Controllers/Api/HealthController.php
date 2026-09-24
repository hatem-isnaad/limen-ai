<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use LimenAi\Agents\AgentDefinitionStore;

class HealthController
{
    public function __invoke(AgentDefinitionStore $definitions): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => (string) config('limen-ai.version', '2.4.0'),
            'database_agents' => $definitions->isAvailable(),
            'streaming' => (bool) config('limen-ai.streaming.enabled', true),
        ]);
    }
}
