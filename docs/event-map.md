# Event Map

## Agent Lifecycle

| Event | Payload highlights | Broadcast | Audit |
|-------|-------------------|-----------|-------|
| AgentStarted | runId, agentKey, conversationId | Yes | Yes |
| AgentThinking | runId, step | Yes | Optional |
| AgentResponding | runId, partial content | Yes | No |
| AgentCompleted | runId, final message | Yes | Yes |
| AgentFailed | runId, error | Yes | Yes |

## Tool Lifecycle

| Event | Payload highlights | Broadcast | Audit |
|-------|-------------------|-----------|-------|
| AgentToolCalling | runId, toolKey, input (redacted) | Yes | Optional |
| ToolStarted | executionId, toolKey | Yes | Yes |
| ToolCompleted | executionId, output (redacted) | Yes | Yes |
| ToolFailed | executionId, error | Yes | Yes |

## Approval Lifecycle

| Event | Payload highlights | Broadcast | Audit |
|-------|-------------------|-----------|-------|
| ApprovalRequested | approvalId, toolKey, summary | Yes | Yes |
| ApprovalGranted | approvalId, resolverId | Yes | Yes |
| ApprovalRejected | approvalId, resolverId | Yes | Yes |

## Workflow Lifecycle

| Event | Payload highlights | Broadcast | Audit |
|-------|-------------------|-----------|-------|
| WorkflowStarted | workflowKey, runId | Optional | Yes |
| WorkflowStepCompleted | stepKey, runId | Optional | Yes |
| WorkflowCompleted | workflowKey, runId | Optional | Yes |
| WorkflowFailed | workflowKey, error | Optional | Yes |

## Conversation

| Event | Payload highlights | Broadcast | Audit |
|-------|-------------------|-----------|-------|
| MessageCreated | messageId, conversationId | Yes | Optional |
| ConversationUpdated | conversationId, state | Yes | No |

## Namespace

All events under `LimenAi\Events\`.

## Listener Examples (host app)

- Update UI via Echo
- Write audit logs
- Track usage/billing (future SaaS)
- Trigger notifications on approval
