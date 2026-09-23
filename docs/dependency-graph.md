# Dependency Graph

## High-Level Module Dependencies

```
Http / Console
    → Application Services (ConversationService, AgentService)
        → Runtime (AgentRuntime, WorkflowEngine)
            → ContextBuilder
            → ToolPipeline
            → ApprovalService
            → CheckpointManager
            → LlmProvider (Providers)
            → Repositories (Agents, Tools, Skills, Conversations, Runs)
            → MemoryStore / KnowledgeRetriever
            → AuthorizationService
            → AuditLogger / UsageTracker
        → Events
            → RealtimeBroadcaster (adapter)
```

## Dependency Rules

| Module | May depend on | Must not depend on |
|--------|---------------|-------------------|
| Contracts | Nothing in src | Concrete implementations |
| Runtime | Contracts, Events, Exceptions | Blade, Pusher, App\ |
| Tools | Contracts, Authorization | Host models |
| Providers | Contracts, HTTP clients | Runtime internals |
| Broadcasting/Adapters | Contracts, Events | Runtime loop |
| Http | Services, Contracts | Pusher directly |
| Knowledge | Contracts, Providers (embeddings) | Host models |
| Security | Contracts | LLM providers |

## Repository Dependencies

```
AgentRuntime
├── AgentRepository
├── ToolRepository
├── ConversationRepository
├── RunRepository
├── LlmProvider
├── AuthorizationService
├── ToolExecutor
├── ApprovalRepository
├── CheckpointStore
├── MemoryRetriever
└── EventDispatcher
```

## Tool Pipeline Dependencies

```
ToolPipeline
├── ToolRepository
├── AuthorizationService
├── Validator
├── ApprovalService
├── IdempotencyGuard
├── RateLimiter
├── ToolExecutor
├── AuditLogger
└── EventDispatcher
```

## Future SaaS Additions (no core rewrite)

```
DatabaseAgentRepository  → replaces ConfigAgentRepository binding
TenantContextResolver  → injected into AuthorizationService
OrganizationRepository → new, optional middleware
```
