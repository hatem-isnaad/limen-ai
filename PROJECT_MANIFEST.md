# Limen AI — Project Manifest

## Project

| Field | Value |
|-------|-------|
| Name | Limen AI |
| Package | `limen-ai/limen-ai` |
| Namespace | `LimenAi\` |
| Repository | https://github.com/hatem-isnaad/limen-ai |
| License | MIT |
| PHP | ^8.2 |
| Laravel | ^11.0 \| ^12.0 |

## Purpose

Reusable Laravel AI Agent Framework for any business domain. First host: Limen 3PL.

## Documentation Index

| File | Purpose |
|------|---------|
| AI_SPEC.md | Master product specification |
| ARCHITECTURE.md | Architecture proposal |
| ARCHITECTURE_RULES.md | Enforced boundaries |
| ROADMAP.md | Phase plan |
| IMPLEMENTATION.md | Build guide |
| STATUS.md | Current progress |
| DECISIONS.md | Architectural decisions log |
| SECURITY.md | Security model |
| TESTING.md | Testing strategy |
| CHANGELOG.md | Version history |
| .ai/MASTER_PROMPT.md | AI coding agent instructions |

## Technical Docs

| File | Purpose |
|------|---------|
| docs/dependency-graph.md | Module dependencies |
| docs/database-proposal.md | Table design |
| docs/event-map.md | Domain events |
| docs/configuration-schema.md | Config reference |
| docs/artisan-command-map.md | CLI commands |
| docs/ui-architecture.md | Chat UI design |
| docs/saas-migration-strategy.md | Future SaaS path |

## Source Structure (Planned)

```
src/
├── Agents/
├── Runtime/
├── Tools/
├── Skills/
├── Workflows/
├── Memory/
├── Knowledge/
├── Authorization/
├── Conversations/
├── Providers/
├── Integrations/
├── Broadcasting/
├── Attachments/
├── Observability/
├── Security/
├── Prompts/
├── Contracts/
├── Events/
├── Exceptions/
├── Console/
└── Http/
```

## Contracts Count

See `src/Contracts/` — skeleton interfaces for all major subsystems.

## Phase Tracking

Current: **Phase 08 — Auth & Authorization**

See [ROADMAP.md](ROADMAP.md) for full phase list.

## Phase 02 Additions

| Component | Implementation |
|-----------|----------------|
| Agent DTO | `ConfigAgentDefinition` |
| Tool DTO | `ConfigToolDefinition` |
| Skill DTO | `ConfigSkillDefinition` |
| Workflow DTO | `ConfigWorkflowDefinition` |
| Repositories | Config-backed, read-only |
| Run context | `RunContextData` |

## Phase 03 Additions

| Component | Implementation |
|-----------|----------------|
| LLM manager | `LlmProviderManager` |
| Embedding manager | `EmbeddingProviderManager` |
| Fake LLM | `FakeLlmProvider` |
| Fake embeddings | `FakeEmbeddingProvider` |
| OpenAI adapter | `OpenAiProvider` |
| LLM response DTO | `LlmResponseData` |

## Phase 04 Additions

| Component | Implementation |
|-----------|----------------|
| Agent resolver | `DefaultAgentResolver` |
| Resolved agent | `ResolvedAgent` |
| Instruction composer | `InstructionComposer` |
| Tool schema builder | `ToolSchemaBuilder` |
| Agent validator | `AgentValidator` |
| Validate command | `limen-ai:validate` |

## Phase 05 Additions

| Component | Implementation |
|-----------|----------------|
| Tool pipeline | `ToolPipeline` |
| Tool executor | `ClassBasedToolExecutor` |
| Input validator | `ToolInputValidator` |
| Authorization | `LaravelAuthorizationService` |
| Audit logger | `LogAuditLogger` |
| Idempotency | `CacheIdempotencyGuard` |
| Data redaction | `SensitiveDataRedactor` |

## Phase 06 Additions

| Component | Implementation |
|-----------|----------------|
| Agent runtime | `DefaultAgentRuntime` |
| Tool call parser | `ToolCallParser` |
| Runtime limits | `RuntimeLimits` |
| Run repository | `InMemoryRunRepository` |
| Checkpoint store | `ArrayCheckpointStore` |

## Phase 07 Additions

| Component | Implementation |
|-----------|----------------|
| Conversation service | `ConversationService` |
| Conversation repo | `InMemoryConversationRepository` |
| Message repo | `InMemoryMessageRepository` |
| Message formatter | `MessageFormatter` |
| Summarizer hook | `NullConversationSummarizer` |
| Events | `MessageCreated`, `ConversationUpdated` |
| Migrations | `limen_ai_conversations`, `limen_ai_messages` |
