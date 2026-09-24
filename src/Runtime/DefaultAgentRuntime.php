<?php

namespace LimenAi\Runtime;

use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Agents\ResolvedAgent;
use LimenAi\Authorization\ApprovalStatus;
use LimenAi\Conversations\ConversationService;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
use LimenAi\Contracts\Memory\MemoryRetriever;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Observability\UsageTracker;
use LimenAi\Observability\MessageUsageEnvelope;
use LimenAi\Observability\RunUsageFinalizer;
use LimenAi\Observability\RunUsageMessageLinker;
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
use LimenAi\Events\AgentStreamDelta;
use LimenAi\Exceptions\StreamingNotSupportedException;
use LimenAi\Contracts\Providers\StreamingLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Providers\LlmStreamChunk;
use LimenAi\Ai\Messages\Message;
use LimenAi\Observability\TokenUsage;
use LimenAi\Support\StructuredOutput;
use LimenAi\Events\ApprovalGranted;
use LimenAi\Events\ApprovalRejected;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Exceptions\ApprovalRequiredException;
use LimenAi\Exceptions\InvalidRunStateException;
use LimenAi\Exceptions\RunNotFoundException;
use LimenAi\Runtime\RunContextData as RunContextDataImpl;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Runtime\AgentStepRunner;

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
        private readonly ContentSanitizer $sanitizer,
        private readonly UsageTracker $usage,
        private readonly UsageReader $usageReader,
        private readonly RunUsageFinalizer $usageFinalizer,
        private readonly RunUsageMessageLinker $usageMessageLinker,
        private readonly AgentStepRunner $agentSteps,
        private readonly Dispatcher $events,
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
        $userTurn = $this->buildUserTurnMessage($userMessage, $context);
        $this->conversations->appendAgentMessage($conversationId, $userTurn);

        $baseMessages = array_merge($history, [$userTurn]);

        $historyCount = count($baseMessages);
        $memoryMessages = $this->memory->retrieve($agentKey, $this->memoryContext($agentKey, $conversationId, $context));
        $knowledgeMessages = $this->knowledge->retrieve($agentKey, $userMessage);
        $messages = array_merge($memoryMessages, $knowledgeMessages, $baseMessages);

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

            $structured = [];

            if (($agent->definition()->outputConfig()['format'] ?? 'text') === 'json') {
                $structured = StructuredOutput::decode($finalMessage, $agent->definition()->outputConfig());
            }

            $usageSummary = $this->usageFinalizer->finalize(
                $runId,
                $agent->providerName(),
                $agent->model(),
                $finalMessage,
                $userMessage,
            );

            $this->persistRun($runId, RunStatus::COMPLETED, [
                'final_message' => $finalMessage,
                'structured_output' => $structured !== [] ? $structured : null,
                'usage_summary' => $usageSummary,
                'current_step' => $limits->currentStep(),
                'tool_call_count' => $limits->toolCallCount(),
                'messages' => $messages,
                'error' => null,
            ]);

            $this->syncConversationMessages(
                $conversationId,
                $messages,
                $historyCount,
                $this->messageUsageEnvelope($runId, $agent, $usageSummary),
                $runId,
            );

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

    public function stream(string $agentKey, string $conversationId, string $userMessage, RunContext $context): \Generator
    {
        if (! (bool) config('limen-ai.streaming.enabled', true)) {
            throw StreamingNotSupportedException::forReason('Streaming is disabled in configuration.');
        }

        $agent = $this->agentResolver->resolve($agentKey);
        $this->authorization->authorizeAgent($agent->definition());
        $this->authorization->validateRunContext($context, $agent->definition());

        if ($agent->toolSchemas() !== [] && ! (bool) config('limen-ai.streaming.allow_with_tools', false)) {
            throw StreamingNotSupportedException::forReason(
                'Streaming is not available for tool-enabled agents. Use run() or enable streaming.allow_with_tools.',
            );
        }

        $this->conversations->ensure($conversationId, $agentKey, $context);

        $userMessage = $this->sanitizer->sanitize($userMessage);

        $history = $this->conversations->historyForAgent($conversationId, $agentKey);
        $userTurn = $this->buildUserTurnMessage($userMessage, $context);
        $this->conversations->appendAgentMessage($conversationId, $userTurn);

        $baseMessages = array_merge($history, [$userTurn]);

        $historyCount = count($baseMessages);
        $memoryMessages = $this->memory->retrieve($agentKey, $this->memoryContext($agentKey, $conversationId, $context));
        $knowledgeMessages = $this->knowledge->retrieve($agentKey, $userMessage);
        $messages = array_merge($memoryMessages, $knowledgeMessages, $baseMessages);

        $runId = $this->runs->create([
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
            'user_id' => $context->userId(),
            'status' => RunStatus::RUNNING,
            'messages' => $messages,
        ]);

        $this->events->dispatch(new AgentStarted($runId, $agentKey, $conversationId, $context));

        $content = '';
        $usage = [];
        $limits = new RuntimeLimits($agent->limits(), microtime(true));
        $useToolLoop = $agent->toolSchemas() !== [] && (bool) config('limen-ai.streaming.allow_with_tools', false);

        try {
            if ($useToolLoop) {
                $content = '';

                foreach ($this->executeLoopStreaming(
                    agent: $agent,
                    runId: $runId,
                    conversationId: $conversationId,
                    messages: $messages,
                    limits: $limits,
                    context: $context,
                ) as $streamChunk) {
                    if (isset($streamChunk->meta['final_content'])) {
                        $content = (string) $streamChunk->meta['final_content'];

                        continue;
                    }

                    if ($streamChunk->done) {
                        continue;
                    }

                    if ($streamChunk->delta !== '' || ($streamChunk->meta['type'] ?? null) !== null) {
                        yield $streamChunk;
                    }
                }
            } else {
                $limits->nextStep();
                $pendingStep = $this->agentSteps->process($agent, $runId, $messages, $limits->currentStep());
                $messages = $pendingStep->messages;

                foreach ($agent->stream($messages, $pendingStep->options) as $chunk) {
                    if ($chunk->delta !== '') {
                        $content .= $chunk->delta;
                    }

                    if ($chunk->usage !== []) {
                        $usage = $chunk->usage;
                    }

                    $this->events->dispatch(new AgentStreamDelta(
                        $runId,
                        $conversationId,
                        $agentKey,
                        $chunk->delta,
                        $chunk->done,
                    ));

                    yield $chunk;
                }
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $structured = [];

            if (($agent->definition()->outputConfig()['format'] ?? 'text') === 'json') {
                $structured = StructuredOutput::decode($content, $agent->definition()->outputConfig());
            }

            if ($usage !== [] && ($summaryPreview = TokenUsage::normalize($usage))['total_tokens'] > 0) {
                $this->usage->recordLlmUsage(
                    $runId,
                    $agent->providerName(),
                    $agent->model(),
                    $usage,
                );
            }

            $usageSummary = $this->usageFinalizer->finalize(
                $runId,
                $agent->providerName(),
                $agent->model(),
                $content,
                $userMessage,
            );

            $this->persistRun($runId, RunStatus::COMPLETED, [
                'final_message' => $content,
                'structured_output' => $structured !== [] ? $structured : null,
                'usage_summary' => $usageSummary,
                'current_step' => $useToolLoop ? $limits->currentStep() : 1,
                'tool_call_count' => $useToolLoop ? $limits->toolCallCount() : 0,
                'messages' => $messages,
                'error' => null,
            ]);

            $usageEnvelope = $this->messageUsageEnvelope($runId, $agent, $usageSummary);

            $assistantMessageId = $this->syncConversationMessages(
                $conversationId,
                $messages,
                $historyCount,
                $usageEnvelope,
                $runId,
            );

            if ($assistantMessageId !== null) {
                $usageEnvelope['message_id'] = $assistantMessageId;
            }

            yield new LlmStreamChunk(
                delta: '',
                done: true,
                meta: [
                    'run_id' => $runId,
                    'usage' => $usageEnvelope,
                ],
            );

            $this->events->dispatch(new AgentCompleted($runId, $agentKey, $conversationId, $content, $context));
        } catch (\Throwable $exception) {
            $this->persistRun($runId, RunStatus::FAILED, [
                'error' => $exception->getMessage(),
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

        $lastUserMessage = $this->lastUserMessageFrom($messages);

        $usageSummary = $this->usageFinalizer->finalize(
            $runId,
            $agent->providerName(),
            $agent->model(),
            $finalMessage,
            $lastUserMessage,
        );

        $this->persistRun($runId, RunStatus::COMPLETED, [
            'final_message' => $finalMessage,
            'usage_summary' => $usageSummary,
            'current_step' => $limits->currentStep(),
            'tool_call_count' => $limits->toolCallCount(),
            'messages' => $messages,
            'error' => null,
        ]);

        $this->syncConversationMessages(
            $conversationId,
            $messages,
            $historyCount,
            $this->messageUsageEnvelope($runId, $agent, $usageSummary),
            $runId,
        );

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
        $content = '';

        foreach ($this->executeLoopStreaming($agent, $runId, $conversationId, $messages, $limits, $context) as $chunk) {
            if (($chunk->meta['final_content'] ?? null) !== null) {
                $content = (string) $chunk->meta['final_content'];
            }
        }

        return $content;
    }

    protected function executeLoopStreaming(
        ResolvedAgent $agent,
        string $runId,
        string $conversationId,
        array &$messages,
        RuntimeLimits $limits,
        RunContext $context,
    ): \Generator {
        while (true) {
            $limits->nextStep();

            $pendingStep = $this->agentSteps->process($agent, $runId, $messages, $limits->currentStep());
            $messages = $pendingStep->messages;

            $toolsDisabled = ($pendingStep->options['omit_tools'] ?? false) || $agent->toolSchemas() === [];
            $provider = $agent->provider();
            $canStream = $provider instanceof StreamingLlmProvider && $provider->supportsStreaming();

            if (
                $canStream
                && ! $toolsDisabled
                && (bool) config('limen-ai.streaming.allow_with_tools', false)
            ) {
                $streamResult = yield from $this->streamAgentStepWithOptionalToolCalls(
                    agent: $agent,
                    runId: $runId,
                    conversationId: $conversationId,
                    messages: $messages,
                    limits: $limits,
                    context: $context,
                    pendingOptions: $pendingStep->options,
                );

                if ($streamResult === 'completed') {
                    return;
                }

                continue;
            }

            if (
                $toolsDisabled
                && $canStream
            ) {
                $content = '';

                foreach ($agent->stream($messages, $pendingStep->options) as $chunk) {
                    if ($chunk->delta !== '') {
                        $content .= $chunk->delta;

                        $this->events->dispatch(new AgentStreamDelta(
                            $runId,
                            $conversationId,
                            $agent->key(),
                            $chunk->delta,
                            false,
                        ));

                        yield new LlmStreamChunk(
                            delta: $chunk->delta,
                            done: false,
                            usage: $chunk->usage,
                            finishReason: $chunk->finishReason,
                        );
                    }

                    if ($chunk->usage !== []) {
                        $this->usage->recordLlmUsage(
                            $runId,
                            $agent->providerName(),
                            $agent->model(),
                            $chunk->usage,
                        );
                    }
                }

                $messages[] = ['role' => 'assistant', 'content' => $content];

                yield new LlmStreamChunk(
                    delta: '',
                    done: true,
                    meta: ['final_content' => $content],
                );

                return;
            }

            $response = $agent->chat($messages, $pendingStep->options);
            $llmUsage = $response->usage();

            if (TokenUsage::normalize($llmUsage)['total_tokens'] > 0) {
                $this->usage->recordLlmUsage(
                    $runId,
                    $agent->providerName(),
                    $agent->model(),
                    $llmUsage,
                );
            }
            $toolCalls = $this->toolCallParser->parse($response);

            if ($toolCalls === []) {
                $content = (string) ($response->content() ?? '');
                $messages[] = ['role' => 'assistant', 'content' => $content];

                yield from $this->emitStreamChunks($runId, $conversationId, $agent->key(), $content);

                yield new LlmStreamChunk(
                    delta: '',
                    done: true,
                    meta: ['final_content' => $content],
                );

                return;
            }

            $limits->recordToolCalls(count($toolCalls));

            $messages[] = [
                'role' => 'assistant',
                'content' => $response->content(),
                'tool_calls' => $response->toolCalls(),
            ];

            $toolContext = $this->toolContext($context, $runId, $conversationId, $agent->key());

            foreach ($toolCalls as $toolCall) {
                yield new LlmStreamChunk(
                    delta: '',
                    done: false,
                    meta: [
                        'type' => 'tool-call',
                        'tool_call_id' => (string) ($toolCall['id'] ?? ''),
                        'tool_name' => (string) ($toolCall['name'] ?? ''),
                        'arguments' => $toolCall['arguments'] ?? [],
                    ],
                );

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

                $output = $result->output();

                yield new LlmStreamChunk(
                    delta: '',
                    done: false,
                    meta: [
                        'type' => 'tool-result',
                        'tool_call_id' => (string) ($toolCall['id'] ?? ''),
                        'result' => $output,
                    ],
                );

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($output, JSON_THROW_ON_ERROR),
                ];
            }
        }
    }

    protected function streamAgentStepWithOptionalToolCalls(
        ResolvedAgent $agent,
        string $runId,
        string $conversationId,
        array &$messages,
        RuntimeLimits $limits,
        RunContext $context,
        array $pendingOptions,
    ): \Generator {
        $content = '';
        $assembledToolCalls = [];
        $llmUsage = [];

        foreach ($agent->stream($messages, $pendingOptions) as $chunk) {
            if ($chunk->delta !== '') {
                $content .= $chunk->delta;
                $this->events->dispatch(new AgentStreamDelta($runId, $conversationId, $agent->key(), $chunk->delta, false));
                yield $chunk;
            } elseif (($chunk->meta['type'] ?? null) === 'tool-call-delta') {
                yield $chunk;
            }

            if (($chunk->meta['type'] ?? null) === 'tool-calls-complete') {
                $assembledToolCalls = array_values($chunk->meta['tool_calls'] ?? []);
                yield $chunk;
            }

            if ($chunk->usage !== []) {
                $llmUsage = $chunk->usage;
            }
        }

        if (TokenUsage::normalize($llmUsage)['total_tokens'] > 0) {
            $this->usage->recordLlmUsage($runId, $agent->providerName(), $agent->model(), $llmUsage);
        }

        if ($assembledToolCalls === []) {
            $messages[] = ['role' => 'assistant', 'content' => $content];
            yield from $this->emitStreamChunks($runId, $conversationId, $agent->key(), $content);
            yield new LlmStreamChunk(delta: '', done: true, meta: ['final_content' => $content]);
            return 'completed';
        }

        $response = LlmResponseData::fromArray([
            'content' => $content !== '' ? $content : null,
            'tool_calls' => $assembledToolCalls,
            'finish_reason' => 'tool_calls',
        ]);

        $toolCalls = $this->toolCallParser->parse($response);

        if ($toolCalls === []) {
            $messages[] = ['role' => 'assistant', 'content' => $content];
            yield from $this->emitStreamChunks($runId, $conversationId, $agent->key(), $content);
            yield new LlmStreamChunk(delta: '', done: true, meta: ['final_content' => $content]);
            return 'completed';
        }

        $limits->recordToolCalls(count($toolCalls));
        $messages[] = ['role' => 'assistant', 'content' => $content !== '' ? $content : null, 'tool_calls' => $assembledToolCalls];
        $toolContext = $this->toolContext($context, $runId, $conversationId, $agent->key());

        foreach ($toolCalls as $toolCall) {
            yield new LlmStreamChunk(delta: '', done: false, meta: ['type' => 'tool-call', 'tool_call_id' => (string) ($toolCall['id'] ?? ''), 'tool_name' => (string) ($toolCall['name'] ?? ''), 'arguments' => $toolCall['arguments'] ?? []]);
            try {
                $result = $this->toolPipeline->execute($toolCall['name'], $toolCall['arguments'], $toolContext);
            } catch (ApprovalRequiredException $exception) {
                $this->saveApprovalCheckpoint($runId, $limits, $messages, $exception, $context, $toolCall['id']);
                throw $exception;
            }
            $output = $result->output();
            yield new LlmStreamChunk(delta: '', done: false, meta: ['type' => 'tool-result', 'tool_call_id' => (string) ($toolCall['id'] ?? ''), 'result' => $output]);
            $messages[] = ['role' => 'tool', 'tool_call_id' => $toolCall['id'], 'content' => json_encode($output, JSON_THROW_ON_ERROR)];
        }

        return 'continue';
    }

    protected function saveApprovalCheckpoint(string $runId, RuntimeLimits $limits, array $messages, ApprovalRequiredException $exception, RunContext $context, ?string $toolCallId = null): string
    {
        $payload = ['tool_call_id' => $toolCallId, 'input' => $exception->input()];
        $approvalId = $this->approvals->request($runId, $exception->tool()->key(), $payload, $context->userId());
        $this->checkpoints->save($runId, $limits->currentStep(), ['messages' => $messages, 'tool_call_count' => $limits->toolCallCount(), 'pending_approval' => ['tool_call_id' => $toolCallId, 'tool_key' => $exception->tool()->key(), 'input' => $exception->input(), 'approval_id' => $approvalId]]);
        $this->events->dispatch(new ApprovalRequested($approvalId, $runId, $exception->tool()->key(), $payload));
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

    protected function toolContext(RunContext $context, string $runId, string $conversationId, string $agentKey): ToolExecutionContext
    {
        if ($context instanceof RunContextDataImpl) {
            return $context->forToolExecution($runId, $conversationId, $agentKey);
        }
        return RunContextDataImpl::make(['user_id' => $context->userId(), 'guest_token' => $context->guestToken(), 'metadata' => $context->metadata(), 'locale' => $context->locale()])->forToolExecution($runId, $conversationId, $agentKey);
    }

    protected function approvedToolContext(RunContext $context, string $runId, string $conversationId, string $agentKey): ToolExecutionContext
    {
        $toolContext = $this->toolContext($context, $runId, $conversationId, $agentKey);
        if ($toolContext instanceof RunContextDataImpl) {
            return $toolContext->withApprovalGranted();
        }
        return RunContextDataImpl::make(['user_id' => $context->userId(), 'guest_token' => $context->guestToken(), 'metadata' => array_merge($context->metadata(), ['approval_granted' => true]), 'locale' => $context->locale()])->forToolExecution($runId, $conversationId, $agentKey);
    }

    protected function persistRun(string $runId, string $status, array $attributes): void
    {
        $this->runs->updateStatus($runId, $status, $attributes);
    }

    protected function syncConversationMessages(string $conversationId, array $messages, int $historyCount, ?array $usageEnvelope = null, ?string $runId = null): ?string
    {
        $newMessages = array_slice($messages, $historyCount);
        if ($newMessages === []) {
            return null;
        }
        if ($usageEnvelope !== null) {
            $newMessages = MessageUsageEnvelope::attachToNewMessages($newMessages, $usageEnvelope);
        }
        $messageIds = $this->conversations->appendAgentMessages($conversationId, $newMessages);
        $assistantMessageId = $this->assistantMessageIdFor($newMessages, $messageIds);
        if ($runId !== null && $assistantMessageId !== null) {
            $this->usageMessageLinker->link($runId, $conversationId, $assistantMessageId);
        }
        return $assistantMessageId;
    }

    protected function assistantMessageIdFor(array $newMessages, array $messageIds): ?string
    {
        for ($i = count($newMessages) - 1; $i >= 0; $i--) {
            if (($newMessages[$i]['role'] ?? '') === 'assistant' && ($newMessages[$i]['content'] ?? '') !== '') {
                return $messageIds[$i] ?? null;
            }
        }
        return null;
    }

    protected function messageUsageEnvelope(string $runId, ResolvedAgent $agent, array $usageSummary): array
    {
        return MessageUsageEnvelope::fromSummary($runId, $agent->providerName(), $agent->model(), $usageSummary, MessageUsageEnvelope::hasEstimatedUsage($this->usageReader->recordsForRun($runId)));
    }

    protected function shouldTrace(): bool
    {
        return (bool) config('limen-ai.observability.trace_enabled', true);
    }

    protected function emitStreamChunks(string $runId, string $conversationId, string $agentKey, string $content): \Generator
    {
        foreach (preg_split('/(\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
            $this->events->dispatch(new AgentStreamDelta($runId, $conversationId, $agentKey, $part, false));
            yield new LlmStreamChunk(delta: $part);
        }
        $this->events->dispatch(new AgentStreamDelta($runId, $conversationId, $agentKey, '', true));
        yield new LlmStreamChunk(delta: '', done: true);
    }

    protected function memoryContext(string $agentKey, string $conversationId, RunContext $context): array
    {
        return ['agent_key' => $agentKey, 'conversation_id' => $conversationId, 'user_id' => $context->userId(), 'guest_token' => $context->guestToken()];
    }

    protected function lastUserMessageFrom(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $content = $messages[$i]['content'] ?? '';
                return is_array($content) ? (string) (collect($content)->firstWhere('type', 'text')['text'] ?? '') : (string) $content;
            }
        }
        return '';
    }

    protected function buildUserTurnMessage(string $userMessage, RunContext $context): array
    {
        $attachments = $context->metadata()['attachments'] ?? [];
        if (! is_array($attachments) || $attachments === []) {
            return ['role' => 'user', 'content' => $userMessage];
        }
        return (new Message('user', $userMessage, array_values($attachments)))->toArray();
    }
}
