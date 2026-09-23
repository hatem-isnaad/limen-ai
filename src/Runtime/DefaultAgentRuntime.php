<?php

namespace LimenAi\Runtime;

use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Agents\AgentPersonaComposer;
use LimenAi\Agents\AgentResponseGuard;
use LimenAi\Agents\ResolvedAgent;
use LimenAi\Authorization\ApprovalStatus;
use LimenAi\Conversations\ConversationService;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Attachments\AgentAttachmentRetriever;
use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
use LimenAi\Contracts\Memory\MemoryRetriever;
use LimenAi\Contracts\Observability\UsageTracker;
use LimenAi\Observability\TraceContext;
use LimenAi\Contracts\Security\ContentSanitizer;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentFailed;
use LimenAi\Events\AgentStarted;
use LimenAi\Events\ApprovalGranted;
use LimenAi\Events\ApprovalRejected;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Exceptions\ApprovalRequiredException;
use LimenAi\Exceptions\InvalidRunStateException;
use LimenAi\Exceptions\RunNotFoundException;
use LimenAi\Runtime\RunContextData as RunContextDataImpl;
use LimenAi\Tools\ToolPipeline;

class DefaultAgentRuntime implements AgentRuntime
{
    public function __construct(
        private readonly AgentResolver $agentResolver,
        private readonly AuthorizationService $authorization,
        private readonly ApprovalRepository $approvals,
        private readonly ToolPipeline $toolPipeline,
        private readonly RunRepository $runs,
        private readonly CheckpointStore $checkpoints,
        private readonly ToolCallParser $toolCallParser,
        private readonly ConversationService $conversations,
        private readonly MemoryRetriever $memory,
        private readonly AgentKnowledgeRetriever $knowledge,
        private readonly AgentAttachmentRetriever $attachments,
        private readonly ContentSanitizer $sanitizer,
        private readonly UsageTracker $usage,
        private readonly Dispatcher $events,
        private readonly AgentPersonaComposer $personaComposer,
        private readonly AgentResponseGuard $responseGuard,
    ) {}

    public function run(string $agentKey, string $conversationId, string $userMessage, RunContext $context): string
    {
        $agent = $this->agentResolver->resolve($agentKey);
        $this->authorization->authorizeAgent($agent->definition());
        $this->authorization->validateRunContext($context, $agent->definition());

        $this->conversations->ensure($conversationId, $agentKey, $context);

        $trace = $this->shouldTrace() ? TraceContext::forRun() : null;

        if ($trace !== null && $context instanceof RunContextDataImpl) {
            $context = $context->withMetadata($trace->toArray());
        }

        $userMessage = $this->sanitizer->sanitize($userMessage);

        $history = $this->conversations->historyForAgent($conversationId, $agentKey);
        $this->conversations->appendUserMessage($conversationId, $userMessage);

        $baseMessages = array_merge($history, [
            ['role' => 'user', 'content' => $userMessage],
        ]);

        $historyCount = count($baseMessages);
        $memoryMessages = $this->memory->retrieve($agentKey, $this->memoryContext($agentKey, $conversationId, $context));
        $knowledgeMessages = $this->knowledge->retrieve($agentKey, $userMessage);
        $attachmentIds = array_values(array_filter((array) ($context->metadata()['attachment_ids'] ?? [])));
        $attachmentMessages = $this->attachments->retrieve($conversationId, $userMessage, $attachmentIds);
        $runtimePersona = $this->runtimePersonaMessage($agent, $context);
        $messages = array_merge(
            $memoryMessages,
            $knowledgeMessages,
            $attachmentMessages,
            $runtimePersona !== null ? [$runtimePersona] : [],
            $baseMessages,
        );

        $runId = $this->runs->create([
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
            'user_id' => $context->userId(),
            'status' => RunStatus::RUNNING,
            'messages' => $messages,
            'trace_id' => $trace?->traceId,
            'span_id' => $trace?->spanId,
        ]);

        $this->events->dispatch(new AgentStarted($runId, $agentKey, $conversationId, $context));

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

            $this->syncConversationMessages($conversationId, $messages, $historyCount);

            $this->events->dispatch(new AgentCompleted($runId, $agentKey, $conversationId, $finalMessage, $context));
            $this->checkpoints->delete($runId);

            return $runId;
        } catch (ApprovalRequiredException $exception) {
            $this->syncConversationMessages($conversationId, $messages, $historyCount);
            $this->conversations->markWaitingApproval($conversationId);

            $approval = $this->approvals->findPendingForRun($runId);

            $this->persistRun($runId, RunStatus::WAITING_APPROVAL, [
                'current_step' => $limits->currentStep(),
                'tool_call_count' => $limits->toolCallCount(),
                'messages' => $messages,
                'metadata' => ['approval_id' => $approval['id'] ?? null],
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
        $run = $this->loadRunWaitingForApproval($runId);
        $agent = $this->agentResolver->resolve((string) $run['agent_key']);

        $this->authorization->authorizeAgent($agent->definition());
        $this->authorization->validateRunContext($context, $agent->definition());

        $checkpoint = $this->loadCheckpoint($runId);
        $state = $checkpoint['state'] ?? $checkpoint;
        $messages = $state['messages'] ?? [];
        $limits = new RuntimeLimits(
            $agent->limits(),
            microtime(true),
            (int) ($checkpoint['step'] ?? 0),
            (int) ($state['tool_call_count'] ?? 0),
        );

        $historyCount = count($messages);
        $conversationId = (string) $run['conversation_id'];
        $approval = $this->approvals->findPendingForRun($runId);

        if ($approval !== null) {
            $resolverId = $context->userId() ?? 0;
            $this->approvals->approve((string) $approval['id'], $resolverId);
            $this->events->dispatch(new ApprovalGranted((string) $approval['id'], $runId, $resolverId));
        }

        $approvedContext = $this->approvedToolContext($context, $runId, $conversationId, $agent->key());

        if ($pending = $state['pending_approval'] ?? null) {
            $result = $this->toolPipeline->execute(
                (string) $pending['tool_key'],
                (array) ($pending['input'] ?? []),
                $approvedContext,
            );

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => (string) ($pending['tool_call_id'] ?? $pending['tool_key']),
                'content' => json_encode($result->output(), JSON_THROW_ON_ERROR),
            ];
        }

        $this->persistRun($runId, RunStatus::RUNNING, []);
        $this->conversations->markActive($conversationId);

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

        $this->syncConversationMessages($conversationId, $messages, $historyCount);

        $this->events->dispatch(new AgentCompleted(
            $runId,
            (string) $run['agent_key'],
            $conversationId,
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

        $agent = $this->agentResolver->resolve((string) $run['agent_key']);
        $this->authorization->authorizeAgent($agent->definition());
        $this->authorization->validateRunContext($context, $agent->definition());

        $this->resolvePendingApproval($runId, $context, ApprovalStatus::REJECTED);

        $this->persistRun($runId, RunStatus::CANCELLED, []);
        $this->checkpoints->delete($runId);
        $this->conversations->markActive((string) $run['conversation_id']);
    }

    public function reject(string $runId, RunContext $context): void
    {
        $run = $this->loadRunWaitingForApproval($runId);
        $agent = $this->agentResolver->resolve((string) $run['agent_key']);

        $this->authorization->authorizeAgent($agent->definition());
        $this->authorization->validateRunContext($context, $agent->definition());

        $this->resolvePendingApproval($runId, $context, ApprovalStatus::REJECTED);

        $this->persistRun($runId, RunStatus::CANCELLED, [
            'error' => 'Approval rejected.',
        ]);
        $this->checkpoints->delete($runId);
        $this->conversations->markActive((string) $run['conversation_id']);
    }

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
            $this->usage->recordLlmUsage(
                $runId,
                $agent->providerName(),
                $agent->model(),
                $response->usage(),
            );
            $toolCalls = $this->toolCallParser->parse($response);

            if ($toolCalls === []) {
                $content = $this->responseGuard->apply(
                    $agent->definition(),
                    (string) ($response->content() ?? ''),
                );
                $messages[] = ['role' => 'assistant', 'content' => $content];

                return $content;
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
                    $this->saveApprovalCheckpoint($runId, $limits, $messages, $exception, $context, $toolCall['id']);
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

    protected function saveApprovalCheckpoint(
        string $runId,
        RuntimeLimits $limits,
        array $messages,
        ApprovalRequiredException $exception,
        RunContext $context,
        ?string $toolCallId = null,
    ): string {
        $payload = [
            'tool_call_id' => $toolCallId,
            'input' => $exception->input(),
        ];

        $approvalId = $this->approvals->request(
            $runId,
            $exception->tool()->key(),
            $payload,
            $context->userId(),
        );

        $this->checkpoints->save($runId, $limits->currentStep(), [
            'messages' => $messages,
            'tool_call_count' => $limits->toolCallCount(),
            'pending_approval' => [
                'tool_call_id' => $toolCallId,
                'tool_key' => $exception->tool()->key(),
                'input' => $exception->input(),
                'approval_id' => $approvalId,
            ],
        ]);

        $this->events->dispatch(new ApprovalRequested(
            $approvalId,
            $runId,
            $exception->tool()->key(),
            $payload,
        ));

        return $approvalId;
    }

    protected function loadRunWaitingForApproval(string $runId): array
    {
        $run = $this->runs->find($runId);

        if ($run === null) {
            throw RunNotFoundException::forId($runId);
        }

        if ($run['status'] !== RunStatus::WAITING_APPROVAL) {
            throw InvalidRunStateException::notWaitingForApproval($runId);
        }

        return $run;
    }

    protected function loadCheckpoint(string $runId): array
    {
        $checkpoint = $this->checkpoints->load($runId);

        if ($checkpoint === null) {
            throw InvalidRunStateException::checkpointMissing($runId);
        }

        return $checkpoint;
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

    protected function approvedToolContext(
        RunContext $context,
        string $runId,
        string $conversationId,
        string $agentKey,
    ): ToolExecutionContext {
        $toolContext = $this->toolContext($context, $runId, $conversationId, $agentKey);

        if ($toolContext instanceof RunContextDataImpl) {
            return $toolContext->withApprovalGranted();
        }

        return RunContextDataImpl::make([
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
            'metadata' => array_merge($context->metadata(), ['approval_granted' => true]),
            'locale' => $context->locale(),
        ])->forToolExecution($runId, $conversationId, $agentKey);
    }

    protected function persistRun(string $runId, string $status, array $attributes): void
    {
        $this->runs->updateStatus($runId, $status, $attributes);
    }

    protected function syncConversationMessages(string $conversationId, array $messages, int $historyCount): void
    {
        $newMessages = array_slice($messages, $historyCount);

        if ($newMessages === []) {
            return;
        }

        $this->conversations->appendAgentMessages($conversationId, $newMessages);
    }

    protected function shouldTrace(): bool
    {
        return (bool) config('limen-ai.observability.trace_enabled', true);
    }

    protected function memoryContext(string $agentKey, string $conversationId, RunContext $context): array
    {
        return [
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
        ];
    }

    protected function runtimePersonaMessage(ResolvedAgent $agent, RunContext $context): ?array
    {
        $addendum = $this->personaComposer->composeRuntimeAddendum($agent->definition(), $context);

        if ($addendum === null || trim($addendum) === '') {
            return null;
        }

        return [
            'role' => 'system',
            'content' => $addendum,
        ];
    }
}
