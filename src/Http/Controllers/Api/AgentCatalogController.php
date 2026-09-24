<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Contracts\Agents\AgentRepository;

/**
 * Read-only catalog for custom UIs (keys, names — not full DB admin).
 */
class AgentCatalogController
{
    public function __construct(
        private readonly AgentRepository $agents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = array_map(
            fn ($agent): array => [
                'key' => $agent->key(),
                'name' => $agent->name(),
                'description' => $agent->description(),
                'model' => $agent->model(),
                'provider' => $agent->provider(),
                'enabled' => $agent->isEnabled(),
            ],
            $this->agents->all(),
        );

        return response()->json(['data' => $data]);
    }

    public function show(string $key): JsonResponse
    {
        $agent = $this->agents->find($key);

        abort_if($agent === null, 404, 'Agent not found.');

        return response()->json([
            'data' => [
                'key' => $agent->key(),
                'name' => $agent->name(),
                'description' => $agent->description(),
                'model' => $agent->model(),
                'provider' => $agent->provider(),
                'tools' => $agent->tools(),
                'skills' => $agent->skills(),
                'output' => $agent->outputConfig(),
                'enabled' => $agent->isEnabled(),
            ],
        ]);
    }
}
