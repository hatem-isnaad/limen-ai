# Limen AI — Implementation Guide

This document tracks how the package will be built, phase by phase.

## Current Phase

**Phase 04 — Agent System**

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

## Next Implementation Tasks (Phase 04)

1. Add agent resolution service (agent + provider + model)
2. Add tool schema builder for LLM function calling format
3. Add agent-level provider override handling
4. Add agent configuration validation command skeleton
5. Add agent resolution tests
