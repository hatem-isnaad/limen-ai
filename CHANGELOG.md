# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Phase 12 workflow engine with agent, tool, approval, and branch steps plus checkpoint resume
- `DefaultWorkflowEngine`, `WorkflowStepRunner`, `WorkflowValidator`, and workflow lifecycle events
- Workflow unit, integration, and feature tests

### Added (Phase 11)

- Phase 11 knowledge / RAG with config and vector retrievers, in-memory vector store, and runtime injection
- `AgentKnowledgeRetriever`, `KnowledgeService`, `KnowledgeFormatter`, and driver-based knowledge bindings
- Knowledge unit, integration, and feature tests

### Added (Phase 10)

- Phase 10 scoped memory system with in-memory/database stores, retriever, and runtime injection
- `MemoryService`, `DefaultMemoryRetriever`, and `limen_ai_memories` migration
- Memory unit, integration, database, and feature tests

### Added (Phase 09)

- Phase 09 persisted run/checkpoint/approval state with database repositories and resume/reject/cancel hardening
- `ApprovalRepository` implementations, approval lifecycle events, and `InvalidRunStateException`
- Database migrations for `limen_ai_runs`, `limen_ai_run_checkpoints`, and `limen_ai_approvals`
- Run-state feature tests including full approval resume flow

### Added (Phase 08)

- Phase 08 authorization hardening with Gate/policy checks, guest session validation, and run context user matching
- `GuestSessionValidator` contract with null and cache drivers
- `UnauthenticatedException` and `RunContextAuthorizationException`
- Authorization unit, integration, and feature tests

### Added (Phase 07)

- Phase 07 conversation persistence with `ConversationService`, in-memory repositories, and runtime history integration
- `MessageFormatter`, `NullConversationSummarizer`, and conversation events (`MessageCreated`, `ConversationUpdated`)
- Database migrations for `limen_ai_conversations` and `limen_ai_messages`
- Conversation unit, integration, and multi-turn feature tests

### Added (Phase 06)

- Phase 06 core agent runtime with `DefaultAgentRuntime` multi-step loop
- `ToolCallParser`, `RuntimeLimits`, in-memory run repository and checkpoint store
- Agent lifecycle events: `AgentStarted`, `AgentCompleted`, `AgentFailed`
- Runtime feature and integration tests with fake LLM + tool loop

### Added (Phase 05)

- Phase 05 tool execution pipeline with authorization, validation, idempotency, audit, and events
- `ClassBasedToolExecutor`, `ToolInputValidator`, `LaravelAuthorizationService`, and `LogAuditLogger`
- Tool exceptions and approval-required gate skeleton

### Added (Phase 04)

- Phase 04 agent resolution with `DefaultAgentResolver`, `ResolvedAgent`, and `InstructionComposer`
- `ToolSchemaBuilder` for LLM function calling schemas
- `AgentValidator` and `limen-ai:validate` Artisan command
- Agent resolution, validation, and feature tests

### Added (Phase 03)

- Phase 03 provider system with `LlmProviderManager` and `EmbeddingProviderManager`
- `FakeLlmProvider`, `FakeEmbeddingProvider`, and `OpenAiProvider` skeleton
- `LlmResponseData` value object and provider exceptions
- Provider unit, integration, and architecture tests

### Added (Phase 02)

- Phase 02 config-backed DTOs and repositories for agents, tools, skills, workflows, and knowledge
- `RunContextData` execution context value object
- Service provider bindings for all definition repositories
- Example skill (`general_assistance`) and knowledge collection (`getting_started`)
- Repository unit, integration, and architecture tests

### Added (Phase 01)

- Initial repository bootstrap
- Master specification and architecture documentation
- Phase 01 contract interfaces (skeleton)
- Configuration schema draft
- `.ai/` coding agent guidance structure
- Dependency graph, database proposal, event map, UI architecture docs
- Security model and testing strategy
- PHPUnit / Testbench scaffold

### Notes

- No runtime implementation yet — architecture phase only
