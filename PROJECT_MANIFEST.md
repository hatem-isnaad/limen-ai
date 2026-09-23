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
| docs/ci.md | CI matrix and merge gates |
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

Current: **Phase 21 — Limen Integration**

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

## Phase 08 Additions

| Component | Implementation |
|-----------|----------------|
| Auth service | Hardened `LaravelAuthorizationService` |
| Guest sessions | `GuestSessionValidator`, null + cache drivers |
| Exceptions | `UnauthenticatedException`, `RunContextAuthorizationException` |
| Context validation | `validateRunContext()` in runtime |

## Phase 09 Additions

| Component | Implementation |
|-----------|----------------|
| Run repository (DB) | `DatabaseRunRepository` |
| Checkpoint store (DB) | `DatabaseCheckpointStore` |
| Approval repository | `InMemoryApprovalRepository`, `DatabaseApprovalRepository` |
| Runtime methods | `resume()`, `cancel()`, `reject()` |
| Events | `ApprovalRequested`, `ApprovalGranted`, `ApprovalRejected` |
| Migrations | `limen_ai_runs`, `limen_ai_run_checkpoints`, `limen_ai_approvals` |

## Phase 10 Additions

| Component | Implementation |
|-----------|----------------|
| Memory store | `InMemoryMemoryStore`, `DatabaseMemoryStore` |
| Memory retriever | `DefaultMemoryRetriever` |
| Memory service | `MemoryService` |
| Formatter | `MemoryFormatter` |
| Migration | `limen_ai_memories` |

## Phase 11 Additions

| Component | Implementation |
|-----------|----------------|
| Agent retriever | `DefaultAgentKnowledgeRetriever` |
| Config retriever | `ConfigKnowledgeRetriever` |
| Vector retriever | `VectorKnowledgeRetriever` |
| Vector store | `InMemoryVectorStore`, `NullVectorStore` |
| Knowledge service | `KnowledgeService` |
| Formatter | `KnowledgeFormatter` |
| Runtime injection | Knowledge after memory, before history |

## Phase 12 Additions

| Component | Implementation |
|-----------|----------------|
| Workflow engine | `DefaultWorkflowEngine` |
| Step runner | `WorkflowStepRunner` |
| Branch routing | `WorkflowBranchEvaluator` |
| Variable templating | `WorkflowVariableResolver` |
| Validation | `WorkflowValidator` |
| Step types | agent, tool, approval, branch |
| Events | `WorkflowStarted`, `WorkflowStepCompleted`, `WorkflowCompleted`, `WorkflowFailed` |

## Phase 13 Additions

| Component | Implementation |
|-----------|----------------|
| HTTP connectors | `ConfigHttpConnectorRepository` |
| HTTP tool executor | `DeclarativeHttpToolExecutor` |
| Request builder | `HttpRequestBuilder` |
| SSRF guard | `SsrfUrlValidator` |
| Secret resolver | `EnvSecretResolver` |
| Validation | `HttpIntegrationValidator` |
| Example tool | `example_http_status` |

## Phase 14 Additions

| Component | Implementation |
|-----------|----------------|
| SSRF guard | `SsrfUrlValidator` with DNS resolution and `assertAllowed()` |
| Security exception | `SecurityException` |
| Content sanitizer | `PromptInjectionSanitizer`, `NullContentSanitizer` |
| Untrusted wrapping | Knowledge, memory, and user message delimiters |
| HTTP hardening | Redirect blocking via `allow_redirects: false` |
| Config | `security.ssrf` and `security.injection` sections |

## Phase 15 Additions

| Component | Implementation |
|-----------|----------------|
| Run jobs | `RunAgentJob`, `ResumeAgentRunJob`, `CancelAgentRunJob`, `RejectAgentRunJob` |
| Dispatcher | `SyncAgentRunDispatcher`, `QueuedAgentRunDispatcher` |
| Broadcast adapter | `PusherBroadcaster`, `NullBroadcaster` |
| Event subscriber | `AgentEventBroadcaster` |
| Run polling | `DefaultRunStatusReader` |
| Config | `queue.agent_runs`, `broadcasting.enabled/driver/connection` |

## Phase 16 Additions

| Component | Implementation |
|-----------|----------------|
| Blade UI | `<x-limen-ai::chatbot />`, `<x-limen-ai::widget />` |
| JS client | `resources/js/limen-ai/chat.js` with Echo + polling |
| HTTP API | Conversation, message, run, approval controllers |
| Access guard | `ConversationAccessGuard` |
| Routes | `routes/limen-ai.php`, `routes/channels.php` |
| Assets | `UiAssets`, publishable CSS/JS/views |

## Phase 17 Additions

| Component | Implementation |
|-----------|----------------|
| Theme resolver | `ThemeResolver`, `ResolvedTheme` |
| Palettes | `ui.palettes.light`, `ui.palettes.dark` |
| Presets | `default`, `arabic` (RTL + Arabic copy) |
| Modes | `light`, `dark`, `auto`, optional toggle |
| Docs | `docs/theming.md` |

## Phase 18 Additions

| Component | Implementation |
|-----------|----------------|
| Trace context | `TraceContext` on runs and tool spans |
| Usage tracking | `LogUsageTracker`, `UsageBuffer`, `UsageReader` |
| Audit export | `AuditBuffer`, `DefaultAuditExporter` |
| Agent audit | `AgentObservabilityListener` |
| Run report | `RunObservabilityReporter`, observability API |
| Docs | `docs/observability.md` |

## Phase 19 Additions

| Component | Implementation |
|-----------|----------------|
| Doctor | `DoctorCommand` (`limen-ai:doctor`) |
| Generators | `MakeAgentCommand`, `MakeToolCommand`, `MakeSkillCommand` |
| Inspection | `ListCommand` (`limen-ai:list`) |
| Stubs | `stubs/*.stub`, `StubGenerator` |
| Publish tag | `limen-ai-stubs` |

## Phase 20 Additions

| Component | Implementation |
|-----------|----------------|
| Module boundaries | `ModuleBoundaryTest`, `ScansPhpSources` |
| Coverage gates | `CriticalCoverageGateTest` |
| Security matrix | `SecurityCriticalMatrixTest` |
| CI workflow | `.github/workflows/tests.yml` |
| Docs | `docs/ci.md`, `composer test:gates` |
