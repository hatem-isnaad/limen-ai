<?php

namespace LimenAi\Observability;

use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentFailed;
use LimenAi\Events\AgentStarted;
use LimenAi\Runtime\RunContextData;

class AgentObservabilityListener
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ?SkillAdherenceReporter $skillAdherence = null,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(AgentStarted::class, [$this, 'handleAgentStarted']);
        $events->listen(AgentCompleted::class, [$this, 'handleAgentCompleted']);
        $events->listen(AgentFailed::class, [$this, 'handleAgentFailed']);
    }

    public function handleAgentStarted(AgentStarted $event): void
    {
        $this->audit->log('agent.started', array_merge(
            $this->context($event->runId, $event->agentKey, $event->conversationId, $event->context),
            $this->skillContext($event->skillKeys),
        ));
    }

    public function handleAgentCompleted(AgentCompleted $event): void
    {
        $this->audit->log('agent.completed', array_merge(
            $this->context($event->runId, $event->agentKey, $event->conversationId, $event->context),
            $this->skillContext($event->skillKeys),
            ['final_message' => $event->finalMessage],
        ));

        $this->skillAdherence?->report(
            $event->runId,
            $event->agentKey,
            $event->skillKeys,
            $event->finalMessage,
        );
    }

    public function handleAgentFailed(AgentFailed $event): void
    {
        $this->audit->log('agent.failed', array_merge(
            $this->context($event->runId, $event->agentKey, $event->conversationId, $event->context),
            ['error' => $event->error],
        ));
    }

    /** @return array<string, mixed> */
    protected function context(string $runId, string $agentKey, string $conversationId, mixed $runContext): array
    {
        $payload = [
            'run_id' => $runId,
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
        ];

        if ($runContext instanceof RunContextData) {
            $payload['user_id'] = $runContext->userId();

            foreach (['trace_id', 'span_id', 'parent_span_id'] as $key) {
                if (isset($runContext->metadata()[$key])) {
                    $payload[$key] = $runContext->metadata()[$key];
                }
            }
        }

        return $payload;
    }

    /** @param  list<string>  $skillKeys */
    protected function skillContext(array $skillKeys): array
    {
        if ($skillKeys === []) {
            return [];
        }

        return [
            'skill_keys' => $skillKeys,
            'skill_count' => count($skillKeys),
        ];
    }
}
