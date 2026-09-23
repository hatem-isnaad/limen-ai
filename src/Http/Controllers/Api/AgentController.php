<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use LimenAi\Agents\AgentProfilePresenter;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;

class AgentController
{
    public function __construct(
        private readonly AgentRepository $agents,
        private readonly AuthorizationService $authorization,
        private readonly AgentProfilePresenter $profiles,
    ) {}

    public function show(string $agentKey): JsonResponse
    {
        $agent = $this->agents->find($agentKey);
        abort_if($agent === null, 404, 'Agent not found.');
        $this->authorization->authorizeAgent($agent);

        return response()->json([
            'agent' => $this->profiles->present($agent),
        ]);
    }
}
