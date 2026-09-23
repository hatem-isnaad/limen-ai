# Limen AI — Implementation Guide

This document tracks how the package will be built, phase by phase.

## Current Phase

**Phase 13 — HTTP Integrations**

## Phase 01 Scope

### In Scope

- Repository bootstrap
- Architecture documentation
- Contract definitions (interfaces only)
- Configuration schema
- AI agent operating files under `.ai/`
- PHPUnit + Testbench scaffold
- Architecture test scaffold

### Out of Scope (Deferred)

- Working runtime loop
- Real LLM provider integrations
- Database migrations implementation
- Blade UI
- Limen host integration
- SaaS features

## Binding Strategy (Planned)

```php
// config/limen-ai.php driven bindings
AgentRepository::class => ConfigAgentRepository::class,
ToolRepository::class => ConfigToolRepository::class,
LlmProvider::class => OpenAiProvider::class, // configurable
RealtimeBroadcaster::class => PusherBroadcaster::class, // configurable
```

All infrastructure bindings registered in `LimenAiServiceProvider`.

## Namespace Convention

- Package namespace: `LimenAi\`
- Contracts: `LimenAi\Contracts\`
- Host app tools: `App\LimenAi\Tools\` (example)

## File Generation Convention

Artisan commands will generate into host app paths by default:

```
app/LimenAi/Agents/
app/LimenAi/Tools/
app/LimenAi/Skills/
app/LimenAi/Workflows/
```

Exact paths configurable via `config/limen-ai.php`.

## Phase Completion Protocol

At the end of each phase:

1. Update STATUS.md
2. Update PROJECT_MANIFEST.md
3. Update AI_SPEC.md checklist
4. Update CHANGELOG.md
5. Run tests + architecture validation
6. Record deferred items explicitly

## Deferred Functionality (Known)

| Item | Target Phase | Notes |
|------|--------------|-------|
| Database agent repository | Post-v1 / SaaS | Contract ready |
| Reverb broadcaster | Phase 15+ | Adapter pattern |
| MCP integrations | Future | Mentioned in spec |
| Multi-tenancy | SaaS phase | DB design prepares for it |
| Attachment RAG pipeline | Phase 11+ | Security first |

## Phase 02 Completed

1. DTO/value objects: `ConfigAgentDefinition`, `ConfigToolDefinition`, `ConfigSkillDefinition`, `ConfigWorkflowDefinition`
2. Config repositories bound in `LimenAiServiceProvider`
3. `RunContextData` for execution context
4. Example skill + knowledge collection wired to example agent
5. Repository unit/integration tests added

## Phase 03 Completed

1. `LlmProviderManager` and `EmbeddingProviderManager`
2. `FakeLlmProvider` with queued responses and call recording
3. `FakeEmbeddingProvider` with deterministic vectors
4. `OpenAiProvider` skeleton using Laravel HTTP client + `Http::fake()` tests
5. Default provider switched to `fake` for safe local/test usage

## Phase 04 Completed

1. `DefaultAgentResolver` resolves agent + provider + tools + skills + limits
2. `ResolvedAgent` exposes tool schemas, chat options, and provider chat helper
3. `ToolSchemaBuilder` converts tool input schema to OpenAI function format
4. `InstructionComposer` merges agent and skill instructions
5. `AgentValidator` and `limen-ai:validate` Artisan command

## Phase 05 Completed

1. `ToolPipeline` orchestrates authorize → validate → approval gate → idempotency → execute
2. `ToolInputValidator` validates tool arguments via Laravel Validator
3. `ClassBasedToolExecutor` executes host app tool classes
4. `LogAuditLogger` + `SensitiveDataRedactor` for audit logging
5. `CacheIdempotencyGuard` and `NullIdempotencyGuard`
6. Tool lifecycle events: `ToolStarted`, `ToolCompleted`, `ToolFailed`

## Phase 06 Completed

1. `DefaultAgentRuntime` multi-step LLM ↔ tool execution loop
2. `ToolCallParser` for OpenAI-style tool calls
3. `RuntimeLimits` for max steps, tool calls, and timeout
4. `InMemoryRunRepository` and `ArrayCheckpointStore` skeletons
5. Approval pause/resume/cancel skeleton with checkpoint state
6. Feature tests with `FakeLlmProvider`

## Phase 07 Completed

1. `InMemoryConversationRepository` and `InMemoryMessageRepository`
2. `ConversationService` with ensure, append, history, and approval state
3. `MessageFormatter` for agent ↔ storage message conversion
4. `NullConversationSummarizer` hook for future context compression
5. Runtime integration: history load, message sync, resume fix
6. Migrations for `limen_ai_conversations` and `limen_ai_messages`
7. Unit, integration, and multi-turn feature tests

## Phase 08 Completed

1. Hardened `LaravelAuthorizationService` with Gate abilities, policy method checks, and subject arguments
2. `validateRunContext()` prevents spoofed `user_id` and validates guest sessions
3. `GuestSessionValidator` contract with `NullGuestSessionValidator` and `CacheGuestSessionValidator`
4. `UnauthenticatedException` and `RunContextAuthorizationException`
5. Authorization config section and service provider bindings
6. Unit, integration, and feature authorization tests

## Phase 09 Completed

1. `DatabaseRunRepository` and `DatabaseCheckpointStore` with query builder persistence
2. `InMemoryApprovalRepository` and `DatabaseApprovalRepository`
3. Migrations for `limen_ai_runs`, `limen_ai_run_checkpoints`, `limen_ai_approvals`
4. Runtime `resume()`, `cancel()`, and `reject()` hardening with auth + approval lifecycle
5. Approval events: `ApprovalRequested`, `ApprovalGranted`, `ApprovalRejected`
6. Tool pipeline bypass for `approval_granted` metadata on resume
7. Database and run-state feature tests

## Phase 10 Completed

1. `MemoryStore` contract with `InMemoryMemoryStore` and `DatabaseMemoryStore`
2. `DefaultMemoryRetriever` with user/conversation/agent scope support
3. `MemoryService` helper for host apps
4. Runtime memory injection before conversation history
5. Migration for `limen_ai_memories`
6. Unit, integration, database, and feature memory tests

## Phase 11 Completed

1. `AgentKnowledgeRetriever` contract with `DefaultAgentKnowledgeRetriever`
2. `ConfigKnowledgeRetriever` keyword scoring and `VectorKnowledgeRetriever` embedding search
3. `InMemoryVectorStore`, `NullVectorStore`, and `KnowledgeService` upsert helper
4. `KnowledgeFormatter` marks retrieved content as untrusted system context
5. Runtime knowledge injection after memory, before conversation history
6. Config-driven driver bindings (`null`, `config`, `vector`) in service provider
7. Unit, integration, and feature knowledge tests

## Phase 12 Completed

1. `DefaultWorkflowEngine` with start, resume, cancel, and reject lifecycle
2. `WorkflowStepRunner` for agent, tool, approval, and branch steps
3. `WorkflowBranchEvaluator` and `WorkflowVariableResolver` for conditional routing and templating
4. Workflow lifecycle events and checkpoint integration with approval repository
5. `WorkflowValidator` wired into `limen-ai:validate`
6. Example workflows in config and unit/integration/feature tests

## Next Implementation Tasks (Phase 13)

1. Declarative HTTP connector config schema
2. HTTP tool executor with SSRF guard hooks
3. Integration tests with `Http::fake()`
