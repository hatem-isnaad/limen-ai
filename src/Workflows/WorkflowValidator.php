<?php

namespace LimenAi\Workflows;

use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowDefinition;
use LimenAi\Contracts\Workflows\WorkflowRepository;

class WorkflowValidator
{
    public function __construct(
        private readonly WorkflowRepository $workflows,
        private readonly AgentRepository $agents,
        private readonly ToolRepository $tools,
    ) {}

    /** @return list<string> */
    public function validateAll(): array
    {
        $errors = [];

        foreach ($this->workflows->all() as $workflow) {
            $errors = array_merge($errors, $this->validateWorkflow($workflow));
        }

        return $errors;
    }

    /** @return list<string> */
    public function validate(string $workflowKey): array
    {
        $workflow = $this->workflows->find($workflowKey);

        if ($workflow === null) {
            return ["Workflow [{$workflowKey}] was not found."];
        }

        return $this->validateWorkflow($workflow);
    }

    /** @return list<string> */
    protected function validateWorkflow(WorkflowDefinition $workflow): array
    {
        $errors = [];
        $key = $workflow->key();

        if (! $workflow instanceof ConfigWorkflowDefinition) {
            return $errors;
        }

        $steps = $workflow->steps();
        $start = $workflow->startStep();

        if ($start === '' || ! isset($steps[$start])) {
            $errors[] = "Workflow [{$key}] is missing a valid start step.";
        }

        foreach ($steps as $stepKey => $step) {
            if (! is_array($step)) {
                $errors[] = "Workflow [{$key}] step [{$stepKey}] must be an array.";

                continue;
            }

            $errors = array_merge($errors, $this->validateStep($key, (string) $stepKey, $step, $steps));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, array<string, mixed>>  $steps
     * @return list<string>
     */
    protected function validateStep(string $workflowKey, string $stepKey, array $step, array $steps): array
    {
        $errors = [];
        $type = (string) ($step['type'] ?? '');

        if ($type === '') {
            $errors[] = "Workflow [{$workflowKey}] step [{$stepKey}] is missing a type.";

            return $errors;
        }

        foreach (['next', 'then', 'else'] as $pointer) {
            if (! isset($step[$pointer]) || $step[$pointer] === '') {
                continue;
            }

            $target = (string) $step[$pointer];

            if (! isset($steps[$target])) {
                $errors[] = "Workflow [{$workflowKey}] step [{$stepKey}] references unknown step [{$target}].";
            }
        }

        if ($type === WorkflowStepType::AGENT) {
            $agentKey = (string) ($step['agent'] ?? '');

            if ($agentKey === '' || $this->agents->find($agentKey) === null) {
                $errors[] = "Workflow [{$workflowKey}] step [{$stepKey}] references unknown agent [{$agentKey}].";
            }
        }

        if ($type === WorkflowStepType::TOOL) {
            $toolKey = (string) ($step['tool'] ?? '');

            if ($toolKey === '' || $this->tools->find($toolKey) === null) {
                $errors[] = "Workflow [{$workflowKey}] step [{$stepKey}] references unknown tool [{$toolKey}].";
            }
        }

        if ($type === WorkflowStepType::BRANCH) {
            if (! isset($step['condition']) || ! is_array($step['condition'])) {
                $errors[] = "Workflow [{$workflowKey}] step [{$stepKey}] is missing branch condition.";
            }

            if (! isset($step['then']) && ! isset($step['else'])) {
                $errors[] = "Workflow [{$workflowKey}] step [{$stepKey}] must define then and/or else targets.";
            }
        }

        return $errors;
    }
}
