<?php

namespace LimenAi\Support;

use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Runtime\RunContextData;

class LimenAiManager
{
    public function __construct(
        private readonly AgentRuntime $runtime,
        private readonly WorkflowEngine $workflows,
        private readonly AgentRepository $agents,
        private readonly ToolRepository $tools,
        private readonly WorkflowRepository $workflowRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function run(string $agentKey, string $conversationId, string $message, array $context = []): string
    {
        return $this->runtime->run(
            $agentKey,
            $conversationId,
            $message,
            RunContextData::make($context),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $context
     */
    public function startWorkflow(string $workflowKey, array $input = [], array $context = []): string
    {
        $workflow = $this->workflowRepository->find($workflowKey);

        if ($workflow === null) {
            throw new \InvalidArgumentException("Workflow [{$workflowKey}] is not registered.");
        }

        return $this->workflows->start($workflow, $input, RunContextData::make($context));
    }

    public function agents(): AgentRepository
    {
        return $this->agents;
    }

    public function tools(): ToolRepository
    {
        return $this->tools;
    }

    public function workflows(): WorkflowRepository
    {
        return $this->workflowRepository;
    }
}
