<?php

namespace LimenAi\Broadcasting;

use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Events\AgentCompleted;
use LimenAi\Events\AgentFailed;
use LimenAi\Events\AgentStarted;
use LimenAi\Events\AgentStreamDelta;
use LimenAi\Events\ApprovalGranted;
use LimenAi\Events\ApprovalRejected;
use LimenAi\Events\ApprovalRequested;
use LimenAi\Events\ConversationUpdated;
use LimenAi\Events\MessageCreated;

class AgentEventBroadcaster
{
    public function __construct(
        private readonly RealtimeBroadcaster $broadcaster,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(AgentStarted::class, [$this, 'handleAgentStarted']);
        $events->listen(AgentCompleted::class, [$this, 'handleAgentCompleted']);
        $events->listen(AgentFailed::class, [$this, 'handleAgentFailed']);
        $events->listen(AgentStreamDelta::class, [$this, 'handleAgentStreamDelta']);
        $events->listen(MessageCreated::class, [$this, 'handleMessageCreated']);
        $events->listen(ConversationUpdated::class, [$this, 'handleConversationUpdated']);
        $events->listen(ApprovalRequested::class, [$this, 'handleApprovalRequested']);
        $events->listen(ApprovalGranted::class, [$this, 'handleApprovalGranted']);
        $events->listen(ApprovalRejected::class, [$this, 'handleApprovalRejected']);
    }

    public function handleAgentStarted(AgentStarted $event): void
    {
        $this->broadcastToConversation($event->conversationId, 'AgentStarted', [
            'run_id' => $event->runId,
            'agent_key' => $event->agentKey,
            'conversation_id' => $event->conversationId,
            'user_id' => $this->userId($event->context),
        ]);
    }

    public function handleAgentCompleted(AgentCompleted $event): void
    {
        $this->broadcastToConversation($event->conversationId, 'AgentCompleted', [
            'run_id' => $event->runId,
            'agent_key' => $event->agentKey,
            'conversation_id' => $event->conversationId,
            'final_message' => $event->finalMessage,
            'user_id' => $this->userId($event->context),
        ]);
    }

    public function handleAgentStreamDelta(AgentStreamDelta $event): void
    {
        $this->broadcastToConversation($event->conversationId, 'AgentStreamDelta', [
            'run_id' => $event->runId,
            'agent_key' => $event->agentKey,
            'conversation_id' => $event->conversationId,
            'delta' => $event->delta,
            'done' => $event->done,
        ]);
    }

    public function handleAgentFailed(AgentFailed $event): void
    {
        $this->broadcastToConversation($event->conversationId, 'AgentFailed', [
            'run_id' => $event->runId,
            'agent_key' => $event->agentKey,
            'conversation_id' => $event->conversationId,
            'error' => $event->error,
            'user_id' => $this->userId($event->context),
        ]);
    }

    public function handleMessageCreated(MessageCreated $event): void
    {
        $this->broadcastToConversation($event->conversationId, 'MessageCreated', [
            'message_id' => $event->messageId,
            'conversation_id' => $event->conversationId,
            'role' => $event->role,
        ]);
    }

    public function handleConversationUpdated(ConversationUpdated $event): void
    {
        $this->broadcastToConversation($event->conversationId, 'ConversationUpdated', [
            'conversation_id' => $event->conversationId,
            'state' => $event->state,
        ]);
    }

    public function handleApprovalRequested(ApprovalRequested $event): void
    {
        $this->broadcastRunUpdate($event->runId, 'ApprovalRequested', [
            'approval_id' => $event->approvalId,
            'run_id' => $event->runId,
            'tool_key' => $event->toolKey,
        ]);
    }

    public function handleApprovalGranted(ApprovalGranted $event): void
    {
        $this->broadcastRunUpdate($event->runId, 'ApprovalGranted', [
            'approval_id' => $event->approvalId,
            'run_id' => $event->runId,
            'resolver_id' => $event->resolverId,
        ]);
    }

    public function handleApprovalRejected(ApprovalRejected $event): void
    {
        $this->broadcastRunUpdate($event->runId, 'ApprovalRejected', [
            'approval_id' => $event->approvalId,
            'run_id' => $event->runId,
            'resolver_id' => $event->resolverId,
        ]);
    }

    /** @param  array<string, mixed>  $payload */
    protected function broadcastToConversation(string $conversationId, string $event, array $payload): void
    {
        $this->broadcaster->broadcast(
            $this->broadcaster->conversationChannel($conversationId),
            $event,
            $payload,
        );
    }

    /** @param  array<string, mixed>  $payload */
    protected function broadcastRunUpdate(string $runId, string $event, array $payload): void
    {
        $this->broadcaster->broadcast(
            $this->broadcaster->conversationChannel('run.'.$runId),
            $event,
            $payload,
        );
    }

    protected function userId(RunContext $context): ?int
    {
        return $context->userId();
    }
}
