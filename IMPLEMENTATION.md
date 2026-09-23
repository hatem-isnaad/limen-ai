# Limen AI — Implementation Guide

This document tracks how the package will be built, phase by phase.

## Current Phase

**Phase 01 — Package Foundation**

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

## Next Implementation Tasks (Phase 02)

1. Add DTO/value objects for AgentDefinition, ToolDefinition, etc.
2. Register contract bindings in service provider
3. Implement config repository stubs (read-only)
4. Add architecture tests for namespace boundaries
5. Add ExampleAgent + ExampleTool config entries
