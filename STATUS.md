# Limen AI — Project Status

**Last updated:** 2026-09-23

## Overall Status

| Area | Status |
|------|--------|
| Repository | Created |
| Architecture docs | Complete |
| Contracts | Complete |
| Config repositories | Complete |
| DTOs / value objects | Complete |
| LLM providers | Fake + OpenAI skeleton |
| Embedding providers | Fake driver |
| Runtime | Core loop complete |
| Conversations | In-memory persistence complete |
| Tool pipeline | Complete |
| UI | Not started |
| Limen integration | Not started |

## Current Phase

**Phase 08 — Auth & Authorization** (Complete)

## Completed

- Phase 01 architecture foundation
- Config-backed DTOs: Agent, Tool, Skill, Workflow
- Config repositories with service provider bindings
- `RunContextData` value object
- Example skill + knowledge collection in config
- Unit and integration tests for repositories
- `FakeLlmProvider` and `FakeEmbeddingProvider`
- `LlmProviderManager` and `EmbeddingProviderManager`
- `OpenAiProvider` skeleton with HTTP mapping
- Provider unit/integration/architecture tests
- `DefaultAgentResolver` and `ResolvedAgent`
- `ToolSchemaBuilder` for LLM function schemas
- `InstructionComposer` for agent + skill prompts
- `AgentValidator` and `limen-ai:validate` command
- `ToolPipeline` with authorization, validation, idempotency, audit, events
- `ClassBasedToolExecutor`, `ToolInputValidator`, `LogAuditLogger`
- `DefaultAgentRuntime` multi-step LLM ↔ tool loop
- `InMemoryRunRepository`, `ArrayCheckpointStore`, `RuntimeLimits`
- Agent lifecycle events and approval pause/resume skeleton
- `ConversationService` with in-memory conversation/message repositories
- Conversation history loaded into agent runtime on each run
- `MessageFormatter`, `NullConversationSummarizer`, conversation events
- Database migrations for `limen_ai_conversations` and `limen_ai_messages`
- Hardened authorization with Gate/policy checks and run context validation
- Guest session validator and `UnauthenticatedException` separation
- Authorization unit, integration, and feature tests

## In Progress

- None

## Blocked

- None

## Next Steps

1. Begin Phase 09 — State & checkpoints (Eloquent repositories)
2. Add queue-dispatched runtime job skeleton
3. Begin Phase 10 — Memory

## Risks

| Risk | Mitigation |
|------|------------|
| Scope explosion | Strict phase gates + architecture tests |
| LLM bypass of auth | Tool pipeline enforces Laravel authorization |
| Tight coupling to Limen | Architecture rules + forbidden dependency tests |
| SaaS rewrite later | Repository abstractions from day one |
