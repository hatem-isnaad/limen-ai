<?php

namespace LimenAi\Workflows;

use Illuminate\Support\Str;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Exceptions\WorkflowNotFoundException;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tools\ToolPipeline;

class WorkflowStepRunner
{
    public function __construct(
        private readonly AgentRuntime $agentRuntime,
        private readonly ToolPipeline $toolPipeline,
        private readonly ToolRepository $tools,
        private readonly RunRepository $runs,
        private readonly WorkflowBranchEvaluator $branches,
        private readonly WorkflowVariableResolver $variables,
    ) {}

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $state
     */
    public function run(
        string $stepKey,
        array $step,
        array $state,
        RunContext $context,
        string $workflowRunId,
    ): WorkflowStepResult {
        $type = (string) ($step['type'] ?? '');

        return match ($type) {
            WorkflowStepType::AGENT => $this->runAgentStep($stepKey, $step, $state, $context),
            WorkflowStepType::TOOL => $this->runToolStep($stepKey, $step, $state, $context, $workflowRunId),
            WorkflowStepType::APPROVAL => $this->runApprovalStep($stepKey, $step),
            WorkflowStepType::BRANCH => $this->runBranchStep($stepKey, $step, $state),
            default => throw WorkflowNotFoundException::forStep((string) ($state['workflow_key'] ?? 'unknown'), $stepKey),
        };
    }

    /** @param  array<string, mixed>  $step  @param  array<string, mixed>  $state */
    protected function runAgentStep(
        string $stepKey,
        array $step,
        array $state,
        RunContext $context,
    ): WorkflowStepResult {
        $agentKey = (string) ($step['agent'] ?? '');
        $message = $this->variables->resolveTemplate((string) ($step['message'] ?? ''), $state);
        $conversationId = (string) ($state['conversation_id'] ?? Str::uuid());

        $agentRunId = $this->agentRuntime->run(
            $agentKey,
            $conversationId,
            $message !== '' ? $message : 'Continue the workflow.',
            $context,
        );

        $agentRun = $this->runs->find($agentRunId);
        $output = (string) ($agentRun['final_message'] ?? '');

        return new WorkflowStepResult(
            stepKey: $stepKey,
            output: [
                'agent_run_id' => $agentRunId,
                'output' => $output,
            ],
            nextStep: $this->resolveNext($step),
        );
    }

    /** @param  array<string, mixed>  $step  @param  array<string, mixed>  $state */
    protected function runToolStep(
        string $stepKey,
        array $step,
        array $state,
        RunContext $context,
        string $workflowRunId,
    ): WorkflowStepResult {
        $toolKey = (string) ($step['tool'] ?? '');

        if ($this->tools->find($toolKey) === null) {
            throw WorkflowNotFoundException::forStep((string) ($state['workflow_key'] ?? 'unknown'), $stepKey);
        }

        $input = $this->variables->resolveArray((array) ($step['input'] ?? []), $state);
        $conversationId = (string) ($state['conversation_id'] ?? Str::uuid());

        $toolContext = RunContextData::make([
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
            'metadata' => array_merge($context->metadata(), [
                'workflow_run_id' => $workflowRunId,
                'workflow_step' => $stepKey,
            ]),
            'locale' => $context->locale(),
        ])->forToolExecution($workflowRunId, $conversationId, 'workflow');

        if ($context->metadata()['approval_granted'] ?? false) {
            $toolContext = $toolContext->withApprovalGranted();
        }

        $result = $this->toolPipeline->execute($toolKey, $input, $toolContext);

        return new WorkflowStepResult(
            stepKey: $stepKey,
            output: ['output' => $result->output()],
            nextStep: $this->resolveNext($step),
        );
    }

    /** @param  array<string, mixed>  $step */
    protected function runApprovalStep(string $stepKey, array $step): WorkflowStepResult
    {
        return new WorkflowStepResult(
            stepKey: $stepKey,
            output: [
                'message' => (string) ($step['message'] ?? 'Approval required.'),
            ],
            nextStep: $this->resolveNext($step),
            paused: true,
        );
    }

    /** @param  array<string, mixed>  $step  @param  array<string, mixed>  $state */
    protected function runBranchStep(string $stepKey, array $step, array $state): WorkflowStepResult
    {
        $nextStep = $this->branches->nextStep($step, $state);

        return new WorkflowStepResult(
            stepKey: $stepKey,
            output: ['next' => $nextStep],
            nextStep: $nextStep,
        );
    }

    /** @param  array<string, mixed>  $step */
    protected function resolveNext(array $step): ?string
    {
        $next = $step['next'] ?? null;

        if ($next === null || $next === '') {
            return null;
        }

        return (string) $next;
    }
}
