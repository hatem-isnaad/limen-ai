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
| 14 | Security Hardening | SSRF, redaction, injection defenses | Pending |
| 15 | Queue & Broadcasting | Jobs, Pusher adapter, channels | Pending |
| 16 | Chat UI | Blade components + JS | Pending |
| 17 | Themes | RTL/LTR, dark/light, customization | Pending |
| 18 | Observability & Audit | Usage, traces, audit logs | Pending |
| 19 | Artisan Developer Tools | make:* commands, stubs, doctor | Pending |
| 20 | Testing & Architecture Validation | Full test matrix + arch tests | Pending |
| 21 | Limen Integration | Host app tools for 3PL demo | Pending |
| 22 | Final Hardening | Performance, docs, release prep | Pending |

## Phase 01 Deliverables

- [x] GitHub repository
- [x] Documentation set
- [x] `.ai/` agent guidance
- [x] Contract interfaces (skeleton)
- [x] Configuration schema
- [x] Dependency graph, event map, DB proposal
- [ ] Architecture approval

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

## Change Control

Phase order may change only after updating:

- ARCHITECTURE.md
- DECISIONS.md
- ROADMAP.md
- STATUS.md
