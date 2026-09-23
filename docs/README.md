# Limen AI — Documentation

Package documentation is organized by topic. Start with the [interactive hub](index.html) or jump to a section below.

## AI agents & contributors

| Document | Description |
|----------|-------------|
| **[../AGENTS.md](../AGENTS.md)** | **Complete AI/contributor reference** — architecture, usage, conventions, cookbook |
| [../.ai/REFERENCE.md](../.ai/REFERENCE.md) | Extended technical reference (bindings, events, test patterns) |
| [../.ai/MASTER_PROMPT.md](../.ai/MASTER_PROMPT.md) | Short boot prompt for AI coding sessions |

## Getting started

| Document | Description |
|----------|-------------|
| [installation.md](installation.md) | **Install the package** — Packagist, path repo, VCS, private registry, monorepo |
| [black-box-host-guide.md](black-box-host-guide.md) | **Black-box install** — env + tools only, no Gates |
| [host-quickstart.md](host-quickstart.md) | **15-minute path** — install → agent → tool → widget |
| [scaling-agents-and-tools.md](scaling-agents-and-tools.md) | Multi-agent layout, tool limits, when to split |
| [HOST-INTEGRATION-AUDIT.md](HOST-INTEGRATION-AUDIT.md) | Host integration quality audit (maintainers) |
| [providers.md](providers.md) | LLM and embedding provider setup |
| [agent-configuration.md](agent-configuration.md) | Persona, tone, language, memory, quality |
| [artisan-command-map.md](artisan-command-map.md) | All `limen-ai:*` Artisan commands |
| [configuration-schema.md](configuration-schema.md) | Full `config/limen-ai.php` reference |
| [release.md](release.md) | Publish, version, and release workflow |

## Architecture

| Document | Description |
|----------|-------------|
| [architecture/ARCHITECTURE.md](architecture/ARCHITECTURE.md) | System architecture and module map |
| [architecture/ARCHITECTURE_RULES.md](architecture/ARCHITECTURE_RULES.md) | Enforced package boundaries |
| [architecture/DECISIONS.md](architecture/DECISIONS.md) | Architectural decision log |
| [dependency-graph.md](dependency-graph.md) | Module dependency graph |
| [database-proposal.md](database-proposal.md) | Database table design |
| [event-map.md](event-map.md) | Domain events |
| [ui-architecture.md](ui-architecture.md) | Chat UI design |

## Project & development

| Document | Description |
|----------|-------------|
| [project/AI_SPEC.md](project/AI_SPEC.md) | Master product specification |
| [project/STATUS.md](project/STATUS.md) | Current implementation status |
| [project/ROADMAP.md](project/ROADMAP.md) | Phase roadmap |
| [project/IMPLEMENTATION.md](project/IMPLEMENTATION.md) | Build guide and deferrals |
| [project/PROJECT_MANIFEST.md](project/PROJECT_MANIFEST.md) | Repo manifest and file index |
| [development/TESTING.md](development/TESTING.md) | Test strategy and patterns |
| [ci.md](ci.md) | CI matrix and merge gates |
| [branching.md](branching.md) | Branch flow: feature → stg → main |

## Integrations & operations

| Document | Description |
|----------|-------------|
| [limen-integration.md](limen-integration.md) | Limen 3PL host app demo |
| [theming.md](theming.md) | Chat UI themes and RTL |
| [observability.md](observability.md) | Traces, audit logs, usage |
| [performance.md](performance.md) | Caching and profiling |
| [saas-migration-strategy.md](saas-migration-strategy.md) | Future SaaS path |

## Root-level files

| File | Description |
|------|-------------|
| [../README.md](../README.md) | Package overview and quick start |
| [../CHANGELOG.md](../CHANGELOG.md) | Version history |
| [../SECURITY.md](../SECURITY.md) | Threat model and security controls |
| [../.env.example](../.env.example) | Environment variable reference (dev) |
