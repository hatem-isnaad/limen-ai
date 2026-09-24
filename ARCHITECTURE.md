# Limen AI — Architecture Proposal

## Overview

Limen AI is a modular Laravel package that separates **AI orchestration** from **application control**.

```
Host Laravel App                Limen AI Package
─────────────────              ──────────────────
Business Services      ←──     Tools (adapters)
Policies / Gates       ←──     Authorization layer
Models / DB            ←──     Repositories (persistence)
Blade / Livewire UI    ←──     Chat components (optional)
Custom Agents/Tools    ←──     Runtime + Contracts
```

## Layered Architecture

```
┌─────────────────────────────────────────────┐
│ HTTP / Console / Jobs / Broadcasting        │
├─────────────────────────────────────────────┤
│ Application Services (Facades, Commands)    │
├─────────────────────────────────────────────┤
│ Runtime (AgentRuntime, WorkflowEngine)      │
├─────────────────────────────────────────────┤
│ Domain (Agents, Tools, Skills, Workflows)   │
├─────────────────────────────────────────────┤
│ Infrastructure (Providers, Stores, Adapters)  │
├─────────────────────────────────────────────┤
│ Contracts (Interfaces / DTOs / Events)      │
└─────────────────────────────────────────────┘
```

## Module Map

| Module | Responsibility |
|--------|----------------|
| `Agents` | Agent definitions, resolution, versioning |
| `Runtime` | Execution loop, checkpoints, limits |
| `Tools` | Tool registry, validation, execution pipeline |
| `Skills` | Composable instruction/capability bundles |
| `Workflows` | Multi-step orchestration with branching |
| `Memory` | Scoped memory retrieval and persistence |
| `Knowledge` | RAG ingestion, chunking, retrieval |
| `Conversations` | Threads, messages, runs |
| `Authorization` | Agent/tool/workflow permission checks |
| `Providers` | LLM + embedding vendor adapters |
| `Integrations` | Declarative HTTP connectors |
| `Broadcasting` | Realtime event adapters (Pusher first) |
| `Attachments` | Upload validation and processing |
| `Observability` | Metrics, traces, usage |
| `Security` | SSRF, redaction, prompt-injection helpers |
| `Prompts` | Prompt assembly and templating |
| `Http` | API controllers (UI-agnostic) |
| `Console` | Artisan commands |

## Runtime Flow

```
User Message
    ↓
ConversationService
    ↓
AgentRuntime::run()
    ↓
ContextBuilder (auth context, memory, knowledge, history)
    ↓
LlmProvider::chat()
    ↓
Tool call requested?
 ┌──┴──┐
No    Yes
│      ↓
│   ToolPipeline
│   ├─ Authorization
│   ├─ Validation
│   ├─ Approval gate
│   ├─ Idempotency check
│   ├─ Execute
│   └─ Audit + Events
│      ↓
└──→ LlmProvider (with tool result)
       ↓
Final response + persist + broadcast
```

## Repository Pattern

All configuration and persistence access goes through contracts:

```
AgentRepository
├── ConfigAgentRepository      (Phase 04)
└── DatabaseAgentRepository    (Future SaaS)

ToolRepository
SkillRepository
WorkflowRepository
KnowledgeRepository
ConversationRepository
RunRepository
ApprovalRepository
AuditLogRepository
```

Runtime depends only on interfaces.

## Tool Execution Pipeline

```
ToolCallProposal (from LLM)
    ↓
ResolveToolDefinition
    ↓
AuthorizeTool
    ↓
ValidateInput (JSON Schema / Laravel Validator)
    ↓
RequiresApproval? → ApprovalService
    ↓
IdempotencyGuard
    ↓
RateLimit + Timeout
    ↓
Execute (class-based or HTTP connector)
    ↓
Normalize output + redact secrets
    ↓
Audit + Events
```

## Provider Abstraction

```
ProviderManager
├── ChatCompletionProvider (OpenAI, Anthropic, Gemini, OpenRouter)
├── EmbeddingProvider
└── (Future) Image/Audio providers
```

Providers are swappable via config and tagged bindings.

## Workflow Engine

Workflows are first-class, not hard-coded in Runtime.

Supported concepts:

- Sequential steps
- Conditional branching
- Loops with limits
- Parallel steps (bounded concurrency)
- Tool / agent / user-input / approval steps
- Variables and checkpoint resume

## Memory & Knowledge Separation

| Memory | Knowledge |
|--------|-----------|
| Conversation/user/agent context | Document collections |
| Summarization over time | Chunk + embed + retrieve |
| Scoped retrieval | Citations + metadata |
| Not blindly injected | Explicit retrieval step |

## Broadcasting

Core runtime emits domain events. Broadcasting is an adapter.

```
Domain Event → RealtimeBroadcaster contract → PusherAdapter (default)
                                            → ReverbAdapter (future)
```

Channel example:

```
private-limen-ai.conversation.{conversationId}
```

Authorization must verify conversation ownership/access.

## Chat UI Architecture

UI is optional and separate from runtime.

```
Backend API (JSON)
    ↕
Blade Components
    ├── <x-limen-ai::chatbot />
    └── <x-limen-ai::widget />

Frontend assets (JS)
    ├── Echo listener
    ├── Streaming handler
    └── Approval UI
```

Themes via publishable config + CSS variables.

## Dependency Graph

See [docs/dependency-graph.md](docs/dependency-graph.md).

## Database

See [docs/database-proposal.md](docs/database-proposal.md).

## Events

See [docs/event-map.md](docs/event-map.md).

## Configuration

See [config/limen-ai.php](config/limen-ai.php) and [docs/configuration-schema.md](docs/configuration-schema.md).

## SaaS Migration Strategy

See [docs/saas-migration-strategy.md](docs/saas-migration-strategy.md).

## Open Architectural Decisions

See [DECISIONS.md](DECISIONS.md).
