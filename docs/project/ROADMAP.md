# Limen AI — Implementation Roadmap

Implementation proceeds in controlled phases. Do not skip phases without architectural review.

| Phase | Name | Goal | Status |
|-------|------|------|--------|
| 01 | Package Foundation | Repo, docs, contracts, config schema | Complete |
| 02 | Contracts & Architecture | DTOs, value objects, binding map | Complete |
| 03 | Provider System | LLM + embedding adapters, fakes | Complete |
| 04 | Agent System | Agent definitions + config repository | Complete |
| 05 | Tool System | Tool pipeline, validation, audit | Complete |
| 06 | Runtime | Core execution loop + limits | Complete |
| 07 | Conversations | Conversations, messages, runs persistence | Complete |
| 08 | Auth & Authorization | Guards, abilities, policies integration | Complete |
| 09 | State & Checkpoints | Resume, cancel, approval wait states | Complete |
| 10 | Memory | Scoped memory stores + retrieval | Complete |
| 11 | Knowledge / RAG | Ingestion, embeddings, vector abstraction | Complete |
| 12 | Workflow Engine | Branching, approval, resume | Complete |
| 13 | HTTP Integrations | Declarative external API tools | Complete |
| 14 | Security Hardening | SSRF, redaction, injection defenses | Complete |
| 15 | Queue & Broadcasting | Jobs, Pusher adapter, channels | Complete |
| 16 | Chat UI | Blade components + JS | Complete |
| 17 | Themes | RTL/LTR, dark/light, customization | Complete |
| 18 | Observability & Audit | Usage, traces, audit logs | Complete |
| 19 | Artisan Developer Tools | make:* commands, stubs, doctor | Complete |
| 20 | Testing & Architecture Validation | Full test matrix + arch tests | Complete |
| 21 | Limen Integration | Host app tools for 3PL demo | Complete |
| 22 | Final Hardening | Performance, docs, release prep | Complete |

## Phase 01 Deliverables

- [x] GitHub repository
- [x] Documentation set
- [x] `.ai/` agent guidance
- [x] Contract interfaces (skeleton)
- [x] Configuration schema
- [x] Dependency graph, event map, DB proposal
- [x] Architecture approval

## Phase 06 Milestone (First Working Runtime)

Minimum for internal testing:

- Config-based agent + tool
- Fake LLM provider
- Single-turn and multi-step tool loop
- Conversation persistence
- Authorization hooks

## Phase 21 Milestone (Limen Demo)

- `GetShipmentStatus` tool in host app
- Approval flow for `SendCustomerMessage`
- Chat UI with broadcasting

## Phase 22 Milestone (v1.0.0)

- Request-scoped agent cache and tool schema memoization
- Release docs, changelog consolidation, and tagged v1.0.0
- Pre-release security checklist and release CI workflow

## Change Control

Phase order may change only after updating:

- docs/architecture/ARCHITECTURE.md
- docs/architecture/DECISIONS.md
- docs/project/ROADMAP.md
- docs/project/STATUS.md
