<?php

namespace LimenAi\Runtime;

use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Agents\ResolvedAgent;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentFailed;
use LimenAi\Events\AgentStarted;
use LimenAi\Exceptions\ApprovalRequiredException;
use LimenAi\Exceptions\RunNotFoundException;
use LimenAi\Runtime\RunContextData as RunContextDataImpl;
use LimenAi\Tools\ToolPipeline;

class DefaultAgentRuntime implements AgentRuntime
{
    public function __construct(
        private readonly AgentResolver $agentResolver,
        private readonly AuthorizationService $authorization,
        private readonly ToolPipeline $toolPipeline,
        private readonly RunRepository $runs,
        private readonly CheckpointStore $checkpoints,
        private readonly ToolCallParser $toolCallParser,
        private readonly Dispatcher $events,
    ) {}

    public function run(string $agentKey, string $conversationId, string $userMessage, RunContext $context): string
    {
        $agent = $this->agentResolver->resolve($agentKey);
        $this->authorization->authorizeAgent($agent->definition());

        $runId = $this->runs->create([
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
            'user_id' => $context->userId(),
            'status' => RunStatus::RUNNING,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        $this->events->dispatch(new AgentStarted($runId, $agentKey, $conversationId, $context));

        $messages = [
            ['role' => 'user', 'content' => $userMessage],
        ];

        $limits = new RuntimeLimits($agent->limits(), microtime(true));

        try {
            $finalMessage = $this->executeLoop(
                agent: $agent,
                runId: $runId,
                conversationId: $conversationId,
                messages: $messages,
                limits: $limits,
                context: $context,
            );

            $this->persistRun($runId, RunStatus::COMPLETED, [
                'final_message' => $finalMessage,
                'current_step' => $limits->currentStep(),
                'tool_call_count' => $limits->toolCallCount(),
                'messages' => $messages,
                'error' => null,
            ]);

            $this->events->dispatch(new AgentCompleted($runId, $agentKey, $conversationId, $finalMessage, $context));
            $this->checkpoints->delete($runId);

            return $runId;
        } catch (ApprovalRequiredException $exception) {
            $this->saveApprovalCheckpoint($runId, $limits, $messages, $exception);

            $this->persistRun($runId, RunStatus::WAITING_APPROVAL, [
                'current_step' => $limits->currentStep(),
                'tool_call_count' => $limits->toolCallCount(),
                'messages' => $messages,
            ]);

            return $runId;
        } catch (\Throwable $exception) {
            $this->persistRun($runId, RunStatus::FAILED, [
                'error' => $exception->getMessage(),
                'current_step' => $limits->currentStep(),
                'tool_call_count' => $limits->toolCallCount(),
                'messages' => $messages,
            ]);

            $this->events->dispatch(new AgentFailed($runId, $agentKey, $conversationId, $exception->getMessage(), $context));

            throw $exception;
        }
    }

    public function resume(string $runId, RunContext $context): void
    {
        $run = $this->runs->find($runId);

        if ($run === null) {
            throw RunNotFoundException::forId($runId);
        }

        if ($run['status'] !== RunStatus::WAITING_APPROVAL) {
            throw new RunNotFoundException("Agent run [{$runId}] is not waiting for approval.");
        }

        $checkpoint = $this->checkpoints->load($runId);

        if ($checkpoint === null) {
            throw RunNotFoundException::forId($runId);
        }

        $agent = $this->agentResolver->resolve((string) $run['agent_key']);
        $state = $checkpoint['state'] ?? $checkpoint;
        $messages = $state['messages'] ?? [];
        $limits = new RuntimeLimits(
            $agent->limits(),
            microtime(true),
            (int) ($checkpoint['step'] ?? 0),
            (int) ($state['tool_call_count'] ?? 0),
        );

        if ($pending = $state['pending_approval'] ?? null) {
            $toolContext = $this->toolContext($context, $runId, (string) $run['conversation_id'], $agent->key());
            $result = $this->toolPipeline->execute(
                (string) $pending['tool_key'],
                (array) ($pending['input'] ?? []),
                $toolContext,
            );

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => (string) ($pending['tool_call_id'] ?? $pending['tool_key']),
                'content' => json_encode($result->output(), JSON_THROW_ON_ERROR),
            ];
        }

        $this->persistRun($runId, RunStatus::RUNNING, []);

        $finalMessage = $this->executeLoop(
            agent: $agent,
            runId: $runId,
            conversationId: (string) $run['conversation_id'],
            messages: $messages,
            limits: $limits,
            context: $context,
        );

        $this->persistRun($runId, RunStatus::COMPLETED, [
            'final_message' => $finalMessage,
            'current_step' => $limits->currentStep(),
            'tool_call_count' => $limits->toolCallCount(),
            'messages' => $messages,
            'error' => null,
        ]);

        $this->events->dispatch(new AgentCompleted(
            $runId,
            (string) $run['agent_key'],
            (string) $run['conversation_id'],
            $finalMessage,
            $context,
        ));

        $this->checkpoints->delete($runId);
    }

    public function cancel(string $runId, RunContext $context): void
    {
        $run = $this->runs->find($runId);

        if ($run === null) {
            throw RunNotFoundException::forId($runId);
        }

        $this->persistRun($runId, RunStatus::CANCELLED, []);
        $this->checkpoints->delete($runId);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    protected function executeLoop(
        ResolvedAgent $agent,
        string $runId,
        string $conversationId,
        array &$messages,
        RuntimeLimits $limits,
        RunContext $context,
    ): string {
        while (true) {
            $limits->nextStep();

            $response = $agent->chat($messages);
            $toolCalls = $this->toolCallParser->parse($response);

            if ($toolCalls === []) {
                return (string) ($response->content() ?? '');
            }

            $limits->recordToolCalls(count($toolCalls));

            $messages[] = [
                'role' => 'assistant',
                'content' => $response->content(),
                'tool_calls' => $response->toolCalls(),
            ];

            $toolContext = $this->toolContext($context, $runId, $conversationId, $agent->key());

            foreach ($toolCalls as $toolCall) {
                try {
                    $result = $this->toolPipeline->execute(
                        $toolCall['name'],
                        $toolCall['arguments'],
                        $toolContext,
                    );
                } catch (ApprovalRequiredException $exception) {
                    $this->saveApprovalCheckpoint($runId, $limits, $messages, $exception, $toolCall['id']);
                    throw $exception;
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($result->output(), JSON_THROW_ON_ERROR),
                ];
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    protected function saveApprovalCheckpoint(
        string $runId,
        RuntimeLimits $limits,
        array $messages,
        ApprovalRequiredException $exception,
        ?string $toolCallId = null,
    ): void {
        $this->checkpoints->save($runId, $limits->currentStep(), [
            'messages' => $messages,
            'tool_call_count' => $limits->toolCallCount(),
            'pending_approval' => [
                'tool_call_id' => $toolCallId,
                'tool_key' => $exception->tool()->key(),
                'input' => $exception->input(),
            ],
        ]);
    }

    protected function toolContext(
        RunContext $context,
        string $runId,
        string $conversationId,
        string $agentKey,
    ): ToolExecutionContext {
        if ($context instanceof RunContextDataImpl) {
            return $context->forToolExecution($runId, $conversationId, $agentKey);
        }

        return RunContextDataImpl::make([
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
            'metadata' => $context->metadata(),
            'locale' => $context->locale(),
        ])->forToolExecution($runId, $conversationId, $agentKey);
    }

    /** @param  array<string, mixed>  $attributes */
    protected function persistRun(string $runId, string $status, array $attributes): void
    {
        $this->runs->updateStatus($runId, $status, $attributes);
    }
}
