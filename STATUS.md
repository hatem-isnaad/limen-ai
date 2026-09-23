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

**Phase 16 — Chat UI** (Complete)

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
- Database-backed run, checkpoint, and approval repositories
- Approval lifecycle events and runtime `reject()` support
- Resume/cancel hardening with persisted approval state
- Scoped memory store with in-memory and database drivers
- Memory retrieval injected into agent runtime context
- Config and vector knowledge retrievers with in-memory vector store
- Knowledge injection into agent runtime (after memory, before history)
- `KnowledgeService` for vector upserts and untrusted knowledge formatting
- Workflow engine with agent, tool, approval, and branch step types
- Workflow checkpoint resume/cancel/reject integrated with approval lifecycle
- Example `example_flow` and `shipment_notify` workflows in config
- Declarative HTTP connectors and `example_http_status` integration tool
- SSRF URL validation and env/config secret resolution for outbound requests
- SSRF DNS resolution checks, `assertAllowed()` API, and redirect blocking on HTTP tools
- `ContentSanitizer` with prompt-injection pattern filtering and untrusted content delimiters
- Sanitization integrated into knowledge, memory formatters, and agent runtime user messages
- Queue-dispatched agent run jobs with sync/queued `AgentRunDispatcher`
- `PusherBroadcaster` and `NullBroadcaster` adapters behind `RealtimeBroadcaster`
- `AgentEventBroadcaster` subscriber for agent, conversation, and approval events
- `RunStatusReader` for async run status polling hooks
- Chat UI Blade components (`chatbot`, `widget`) with themed CSS/JS client
- HTTP API for conversations, messages, runs, and approvals
- Echo-ready private channel authorization for conversation updates

## In Progress

- None

## Blocked

- None

## Next Steps

1. Begin Phase 17 — Themes
2. Expand RTL/dark mode theming and publishable asset overrides
3. Begin Phase 18 — Observability & Audit

## Risks

| Risk | Mitigation |
|------|------------|
| Scope explosion | Strict phase gates + architecture tests |
| LLM bypass of auth | Tool pipeline enforces Laravel authorization |
| Tight coupling to Limen | Architecture rules + forbidden dependency tests |
| SaaS rewrite later | Repository abstractions from day one |
