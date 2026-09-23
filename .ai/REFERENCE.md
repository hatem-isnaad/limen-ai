# Limen AI — Extended Technical Reference (AI Agents)

> **Start with:** [`AGENTS.md`](../AGENTS.md) (root) for the complete contributor guide.  
> This file adds implementation depth for AI coding sessions.

---

## Service provider binding map

File: `src/LimenAiServiceProvider.php`

| Contract | Default implementation | Config override |
|----------|------------------------|-----------------|
| `AgentRepository` | `ConfigAgentRepository` | `repositories.agent` |
| `ToolRepository` | `ConfigToolRepository` | `repositories.tool` |
| `SkillRepository` | `ConfigSkillRepository` | `repositories.skill` |
| `WorkflowRepository` | `ConfigWorkflowRepository` | `repositories.workflow` |
| `KnowledgeRepository` | `ConfigKnowledgeRepository` | `repositories.knowledge` |
| `AgentRuntime` | `DefaultAgentRuntime` | — |
| `WorkflowEngine` | `DefaultWorkflowEngine` | — |
| `RunRepository` | `InMemoryRunRepository` | `runtime.run_repository` |
| `CheckpointStore` | `ArrayCheckpointStore` | `runtime.checkpoint_store` |
| `ApprovalRepository` | `InMemoryApprovalRepository` | `runtime.approval_repository` |
| `ConversationRepository` | `InMemoryConversationRepository` | `conversations.repository` |
| `MessageRepository` | `InMemoryMessageRepository` | `conversations.message_repository` |
| `MemoryStore` | `InMemoryMemoryStore` | `memory.store` |
| `AttachmentStore` | `InMemoryAttachmentStore` | `attachments.store` (or `NullAttachmentStore` when disabled) |
| `LlmProvider` | via `LlmProviderManager` | `providers.default` + per-agent `provider` |
| `RealtimeBroadcaster` | `NullBroadcaster` / `PusherBroadcaster` | `broadcasting.driver` |
| `AgentRunDispatcher` | `SyncAgentRunDispatcher` | `queue.agent_runs` → `QueuedAgentRunDispatcher` |
| `AuthorizationService` | `LaravelAuthorizationService` | — |
| `ContentSanitizer` | `PromptInjectionSanitizer` | `security.sanitizer` |
| `UrlValidator` | `SsrfUrlValidator` | — |
| `AuditLogger` | `LogAuditLogger` | — |
| `UsageTracker` | `LogUsageTracker` / `NullUsageTracker` | `observability.usage_tracking_enabled` |
| `LimenAiManager` | singleton | Facade accessor |

**Pattern for new bindings:**

```php
$this->app->singleton(MyContract::class, function ($app): MyContract {
    $class = $app['config']->get('limen-ai.my_feature.driver');
    return $app->make($class);
});
```

Register in a dedicated `registerMyFeature()` method called from `register()`.

---

## Event catalog (subscribe in host app)

| Event | When |
|-------|------|
| `AgentStarted` | Run begins |
| `AgentCompleted` | Run finishes successfully |
| `AgentFailed` | Run throws |
| `ToolStarted` / `ToolCompleted` / `ToolFailed` | Tool pipeline lifecycle |
| `ApprovalRequired` / `ApprovalResolved` | Human-in-the-loop gates |
| `ConversationCreated` / `MessageCreated` | Conversation API |
| `AttachmentUploaded` / `AttachmentProcessed` | File pipeline |
| `WorkflowStarted` / `WorkflowCompleted` / `WorkflowFailed` | Workflow engine |

Full map: `docs/event-map.md`  
Built-in subscriber: `AgentEventBroadcaster` → Pusher channels.

---

## Test patterns

### Fake LLM in feature test

```php
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;

$fake = app(FakeLlmProvider::class);
$fake->queueResponse(LlmResponseData::fromArray([
    'content' => 'Done.',
    'finish_reason' => 'stop',
]));
```

### Tool call response

```php
$fake->queueResponse(LlmResponseData::fromArray([
    'content' => null,
    'tool_calls' => [[
        'id' => 'call_1',
        'type' => 'function',
        'function' => [
            'name' => 'example_echo',
            'arguments' => json_encode(['message' => 'hi']),
        ],
    ]],
    'finish_reason' => 'tool_calls',
]));
// Queue a second response for after tool execution
$fake->queueResponse(LlmResponseData::fromArray([
    'content' => 'Echo received.',
    'finish_reason' => 'stop',
]));
```

### TestCase config overrides

`tests/TestCase.php` sets safe defaults:

- `limen-ai.tools.example_echo.class` → test stub
- `limen-ai.attachments.enabled` → true
- `limen-ai.attachments.store` → `InMemoryAttachmentStore`

### Database tests

Extend `DatabaseTestCase` — runs migrations including `limen_ai_*` tables.

---

## Architecture test files (must pass)

| File | Enforces |
|------|----------|
| `ModuleBoundaryTest.php` | Import rules between modules |
| `PackageBoundaryTest.php` | No `App\`, definition/runtime separation |
| `CriticalCoverageGateTest.php` | Security-critical files have tests |
| `ReleaseReadinessTest.php` | CHANGELOG, SECURITY.md, release docs |
| `SecurityCriticalMatrixTest.php` | SSRF, injection, redaction smoke |

Run: `composer test:gates`

---

## Publish tags (host app)

| Tag | Publishes |
|-----|-----------|
| `limen-ai-config` | `config/limen-ai.php` |
| `limen-ai-env` | `.env.limen-ai.example` |
| `limen-ai-migrations` | `database/migrations/*` |
| `limen-ai-stubs` | Generator stubs |
| `limen-ai-ui` | Views + assets |
| `limen-ai-limen-demo` | Limen 3PL demo config/tools |

---

## Naming conventions

| Item | Convention | Example |
|------|------------|---------|
| Config keys | `snake_case` | `example_echo`, `limen_3pl` |
| Tool classes (host) | `{Verb}{Noun}Tool` | `GetShipmentStatusTool` |
| Repositories | `Config*Repository`, `Database*Repository`, `InMemory*Repository` | `ConfigAgentRepository` |
| Definitions | `Config*Definition` | `ConfigToolDefinition` |
| Null/disabled | `Null*` | `NullAttachmentStore` |
| Commands | `limen-ai:{action}` | `limen-ai:agent:test` |
| Events | past tense | `ToolCompleted` |
| Exceptions | descriptive | `ApprovalRequiredException` |

---

## Deferred by design (do not implement without spec)

See `docs/project/IMPLEMENTATION.md`:

- Database agent repository (SaaS target)
- Reverb broadcaster adapter
- MCP integrations
- Multi-tenancy
- OpenTelemetry export

Contracts exist for future swap — do not break them.

---

## File edit checklist for AI agents

When modifying this package, check all that apply:

- [ ] `src/` change has corresponding test
- [ ] `config/limen-ai.php` change reflected in `.env.example` + `stubs/limen-ai.env.example`
- [ ] New public API documented in README + `docs/`
- [ ] `CHANGELOG.md` Unreleased section updated
- [ ] Architecture boundaries preserved (`composer test:gates`)
- [ ] No `App\` imports added to package code
- [ ] Security-sensitive path added to `CriticalCoverageGateTest` if applicable
