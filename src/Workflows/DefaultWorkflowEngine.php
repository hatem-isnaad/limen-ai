<?php

namespace LimenAi\Workflows;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use LimenAi\Authorization\ApprovalStatus;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Workflows\WorkflowDefinition;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Events\ApprovalGranted;
use LimenAi\Events\ApprovalRejected;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Events\WorkflowCompleted;
use LimenAi\Events\WorkflowFailed;
use LimenAi\Events\WorkflowStarted;
use LimenAi\Events\WorkflowStepCompleted;
use LimenAi\Exceptions\InvalidRunStateException;
use LimenAi\Exceptions\RunNotFoundException;
use LimenAi\Exceptions\WorkflowDisabledException;
use LimenAi\Exceptions\WorkflowNotFoundException;
use LimenAi\Support\Enablement;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;

class DefaultWorkflowEngine implements WorkflowEngine
{
    public function __construct(
        private readonly WorkflowRepository $workflows,
        private readonly WorkflowStepRunner $steps,
        private readonly RunRepository $runs,
        private readonly CheckpointStore $checkpoints,
        private readonly ApprovalRepository $approvals,
        private readonly Dispatcher $events,
    ) {}

    /** @param  array<string, mixed>  $input */
    public function start(WorkflowDefinition $workflow, array $input, RunContext $context): string
    {
        $this->assertWorkflowIsEnabled($workflow);

        $startStep = $this->startStepKey($workflow);

        $runId = $this->runs->create([
            'type' => 'workflow',
            'workflow_key' => $workflow->key(),
            'status' => RunStatus::RUNNING,
            'input' => $input,
            'metadata' => [
                'workflow_key' => $workflow->key(),
            ],
        ]);

        $state = [
            'workflow_key' => $workflow->key(),
            'current_step' => $startStep,
            'input' => $input,
            'step_outputs' => [],
            'conversation_id' => (string) Str::uuid(),
            'step_index' => 0,
        ];

        $this->events->dispatch(new WorkflowStarted($runId, $workflow->key(), $context));

        try {
            $this->execute($runId, $workflow, $state, $context);

            return $runId;
        } catch (\Throwable $exception) {
            $this->persistFailure($runId, $workflow->key(), $exception->getMessage(), $context);

            throw $exception;
        }
    }

    public function resume(string $runId, RunContext $context): void
    {
        $run = $this->loadRunWaitingForApproval($runId);
        $workflow = $this->loadWorkflow((string) $run['workflow_key']);
        $checkpoint = $this->loadCheckpoint($runId);
        $state = $checkpoint['state'] ?? $checkpoint;

        $approval = $this->approvals->findPendingForRun($runId);

        if ($approval !== null) {
            $resolverId = $context->userId() ?? 0;
            $this->approvals->approve((string) $approval['id'], $resolverId);
            $this->events->dispatch(new ApprovalGranted((string) $approval['id'], $runId, $resolverId));
        }

        $currentStep = (string) ($state['current_step'] ?? '');
        $step = $workflow->steps()[$currentStep] ?? null;
        $nextStep = is_array($step) ? ($step['next'] ?? null) : null;

        if ($nextStep === null || $nextStep === '') {
            $this->completeRun($runId, $workflow->key(), $state, $context);

            return;
        }

        $state['current_step'] = (string) $nextStep;
        $this->runs->updateStatus($runId, RunStatus::RUNNING, []);

        $context = $this->contextWithApprovalGranted($context);

        try {
            $this->execute($runId, $workflow, $state, $context);
        } catch (\Throwable $exception) {
            $this->persistFailure($runId, $workflow->key(), $exception->getMessage(), $context);

            throw $exception;
        }
    }

    public function cancel(string $runId, RunContext $context): void
    {
        $run = $this->runs->find($runId);

        if ($run === null) {
            throw RunNotFoundException::forId($runId);
        }

        $this->assertWorkflowRun($run);

        $this->resolvePendingApproval($runId, $context, ApprovalStatus::REJECTED);
        $this->runs->updateStatus($runId, RunStatus::CANCELLED, []);
        $this->checkpoints->delete($runId);
    }

    public function reject(string $runId, RunContext $context): void
    {
        $run = $this->loadRunWaitingForApproval($runId);
        $workflowKey = (string) $run['workflow_key'];

        $this->resolvePendingApproval($runId, $context, ApprovalStatus::REJECTED);
        $this->runs->updateStatus($runId, RunStatus::CANCELLED, [
            'error' => 'Workflow approval rejected.',
        ]);
        $this->checkpoints->delete($runId);
        $this->events->dispatch(new WorkflowFailed($runId, $workflowKey, 'Workflow approval rejected.', $context));
    }

    /** @param  array<string, mixed>  $state */
    protected function execute(
        string $runId,
        WorkflowDefinition $workflow,
        array $state,
        RunContext $context,
    ): void {
        while (true) {
            $stepKey = (string) ($state['current_step'] ?? '');

            if ($stepKey === '') {
                $this->completeRun($runId, $workflow->key(), $state, $context);

                return;
            }

            $step = $workflow->steps()[$stepKey] ?? null;

            if (! is_array($step)) {
                throw WorkflowNotFoundException::forStep($workflow->key(), $stepKey);
            }

            $result = $this->steps->run($stepKey, $step, $state, $context, $runId);
            $state['step_outputs'][$stepKey] = $result->output();
            $state['step_index'] = (int) ($state['step_index'] ?? 0) + 1;

            $this->events->dispatch(new WorkflowStepCompleted(
                $runId,
                $workflow->key(),
                $stepKey,
                $result->output(),
                $context,
            ));

            if ($result->paused()) {
                $this->pauseForApproval($runId, $stepKey, $state, $result, $context);

                return;
            }

            $nextStep = $result->nextStep();

            if ($nextStep === null || $nextStep === '') {
                $this->completeRun($runId, $workflow->key(), $state, $context);

                return;
            }

            $state['current_step'] = $nextStep;
        }
    }

    /** @param  array<string, mixed>  $state  @param  array<string, mixed>  $resultOutput */
    protected function pauseForApproval(
        string $runId,
        string $stepKey,
        array $state,
        WorkflowStepResult $result,
        RunContext $context,
    ): void {
        $approvalId = $this->approvals->request(
            $runId,
            "workflow:{$stepKey}",
            [
                'step_key' => $stepKey,
                'message' => $result->output()['message'] ?? 'Approval required.',
            ],
            $context->userId(),
        );

        $this->checkpoints->save($runId, (int) ($state['step_index'] ?? 0), $state);
        $this->runs->updateStatus($runId, RunStatus::WAITING_APPROVAL, [
            'metadata' => ['approval_id' => $approvalId],
        ]);

        $this->events->dispatch(new ApprovalRequested(
            $approvalId,
            $runId,
            "workflow:{$stepKey}",
            ['step_key' => $stepKey],
        ));
    }

    /** @param  array<string, mixed>  $state */
    protected function completeRun(
        string $runId,
        string $workflowKey,
        array $state,
        RunContext $context,
    ): void {
        $this->runs->updateStatus($runId, RunStatus::COMPLETED, [
            'step_outputs' => $state['step_outputs'] ?? [],
            'error' => null,
        ]);
        $this->checkpoints->delete($runId);
        $this->events->dispatch(new WorkflowCompleted($runId, $workflowKey, $state['step_outputs'] ?? [], $context));
    }

    protected function persistFailure(
        string $runId,
        string $workflowKey,
        string $message,
        RunContext $context,
    ): void {
        $this->runs->updateStatus($runId, RunStatus::FAILED, [
            'error' => $message,
        ]);
        $this->events->dispatch(new WorkflowFailed($runId, $workflowKey, $message, $context));
    }

    protected function startStepKey(WorkflowDefinition $workflow): string
    {
        $start = $workflow->startStep();

        if ($start !== '' && isset($workflow->steps()[$start])) {
            return $start;
        }

        throw InvalidRunStateException::invalidWorkflowStart($workflow->key());
    }

    /** @return array<string, mixed> */
    protected function loadRunWaitingForApproval(string $runId): array
    {
        $run = $this->runs->find($runId);

        if ($run === null) {
            throw RunNotFoundException::forId($runId);
        }

        $this->assertWorkflowRun($run);

        if ($run['status'] !== RunStatus::WAITING_APPROVAL) {
            throw InvalidRunStateException::notWaitingForApproval($runId);
        }

        return $run;
    }

    /** @return array<string, mixed> */
    protected function loadCheckpoint(string $runId): array
    {
        $checkpoint = $this->checkpoints->load($runId);

        if ($checkpoint === null) {
            throw InvalidRunStateException::checkpointMissing($runId);
        }

        return $checkpoint;
    }

    protected function loadWorkflow(string $workflowKey): WorkflowDefinition
    {
        $workflow = $this->workflows->find($workflowKey);

        if ($workflow === null) {
            throw WorkflowNotFoundException::forKey($workflowKey);
        }

        $this->assertWorkflowIsEnabled($workflow);

        return $workflow;
    }

    protected function assertWorkflowIsEnabled(WorkflowDefinition $workflow): void
    {
        if (! Enablement::isEnabled($workflow)) {
            throw WorkflowDisabledException::forWorkflow($workflow->key());
        }
    }

    /** @param  array<string, mixed>  $run */
    protected function assertWorkflowRun(array $run): void
    {
        if (($run['type'] ?? null) !== 'workflow') {
            throw InvalidRunStateException::notWorkflowRun((string) ($run['id'] ?? ''));
        }
    }

    protected function contextWithApprovalGranted(RunContext $context): RunContext
    {
        if ($context instanceof RunContextData) {
            return $context->withApprovalGranted();
        }

        return RunContextData::make([
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
            'metadata' => array_merge($context->metadata(), ['approval_granted' => true]),
            'locale' => $context->locale(),
        ]);
    }

    protected function resolvePendingApproval(string $runId, RunContext $context, string $status): void
    {
        $approval = $this->approvals->findPendingForRun($runId);

        if ($approval === null) {
            return;
        }

        $resolverId = $context->userId() ?? 0;
        $approvalId = (string) $approval['id'];

        if ($status === ApprovalStatus::APPROVED) {
            $this->approvals->approve($approvalId, $resolverId);
            $this->events->dispatch(new ApprovalGranted($approvalId, $runId, $resolverId));

            return;
        }

        $this->approvals->reject($approvalId, $resolverId);
        $this->events->dispatch(new ApprovalRejected($approvalId, $runId, $resolverId));
    }
}
