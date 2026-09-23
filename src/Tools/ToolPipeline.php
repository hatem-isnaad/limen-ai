<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Observability\UsageTracker;
use LimenAi\Observability\TraceContext;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\IdempotencyGuard;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Events\ToolCompleted;
use LimenAi\Events\ToolFailed;
use LimenAi\Events\ToolStarted;
use LimenAi\Exceptions\ApprovalRequiredException;
use LimenAi\Exceptions\ToolExecutionException;
use LimenAi\Exceptions\ToolNotFoundException;
use LimenAi\Security\SensitiveDataRedactor;

class ToolPipeline
{
    public function __construct(
        private readonly ToolRepository $tools,
        private readonly AuthorizationService $authorization,
        private readonly ToolInputValidator $validator,
        private readonly ToolExecutor $executor,
        private readonly IdempotencyGuard $idempotency,
        private readonly AuditLogger $audit,
        private readonly UsageTracker $usage,
        private readonly SensitiveDataRedactor $redactor,
        private readonly Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function execute(string $toolKey, array $input, ToolExecutionContext $context): ToolExecutionResult
    {
        $tool = $this->tools->find($toolKey);

        if ($tool === null) {
            throw ToolNotFoundException::forKey($toolKey);
        }

        $executionId = (string) Str::uuid();

        $this->authorization->authorizeTool($tool);

        $validatedInput = $this->validator->validate($tool, $input);

        if ($tool->requiresConfirmation() && ! ($context->metadata()['approval_granted'] ?? false)) {
            throw ApprovalRequiredException::forTool($tool, $validatedInput, $context);
        }

        $idempotencyKey = $this->resolveIdempotencyKey($tool, $context);

        if ($idempotencyKey !== null && $this->idempotency->has($idempotencyKey)) {
            $cached = $this->idempotency->get($idempotencyKey) ?? [];

            $this->audit->log('tool.idempotent_hit', $this->auditContext($tool, $validatedInput, $context, $executionId));

            return ToolExecutionResult::cached($cached, $executionId);
        }

        $this->events->dispatch(new ToolStarted($executionId, $tool, $this->redactor->redact($validatedInput), $context));
        $this->audit->log('tool.started', $this->auditContext($tool, $validatedInput, $context, $executionId));

        $startedAt = microtime(true);

        try {
            $output = $this->executor->execute($tool, $validatedInput, $context);
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($idempotencyKey !== null) {
                $this->idempotency->remember($idempotencyKey, $output);
            }

            $this->events->dispatch(new ToolCompleted(
                $executionId,
                $tool,
                $this->redactor->redact($output),
                $durationMs,
                $context,
            ));

            $this->audit->log('tool.completed', array_merge(
                $this->auditContext($tool, $validatedInput, $context, $executionId),
                ['duration_ms' => $durationMs],
            ));

            if ($context->runId() !== '') {
                $this->usage->recordToolExecution($context->runId(), $tool->key(), $durationMs);
            }

            return ToolExecutionResult::fresh($output, $durationMs, $executionId);
        } catch (ApprovalRequiredException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->events->dispatch(new ToolFailed($executionId, $tool, $exception->getMessage(), $context));
            $this->audit->log('tool.failed', array_merge(
                $this->auditContext($tool, $validatedInput, $context, $executionId),
                ['error' => $exception->getMessage()],
            ));

            throw ToolExecutionException::forTool($tool->key(), $exception);
        }
    }

    protected function resolveIdempotencyKey(ToolDefinition $tool, ToolExecutionContext $context): ?string
    {
        $metadataKey = $context->metadata()['idempotency_key'] ?? null;

        if (is_string($metadataKey) && $metadataKey !== '') {
            return $tool->key().':'.$metadataKey;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function auditContext(
        ToolDefinition $tool,
        array $input,
        ToolExecutionContext $context,
        string $executionId,
    ): array {
        $payload = [
            'execution_id' => $executionId,
            'tool_key' => $tool->key(),
            'run_id' => $context->runId(),
            'conversation_id' => $context->conversationId(),
            'agent_key' => $context->agentKey(),
            'user_id' => $context->userId(),
            'input' => $this->redactor->redact($input),
        ];

        $traceId = $context->metadata()['trace_id'] ?? null;

        if (is_string($traceId) && $traceId !== '') {
            $parentSpanId = $context->metadata()['span_id'] ?? null;
            $payload = array_merge($payload, TraceContext::child($traceId, is_string($parentSpanId) ? $parentSpanId : null)->toArray());
        }

        return $payload;
    }
}
