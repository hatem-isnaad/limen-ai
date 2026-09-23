# Limen AI — Master Specification

This document is the canonical product specification for the Limen AI Laravel package.

## Vision

Build a large, reusable Laravel AI Agent Framework called **Limen AI**.

- First delivery: installable Laravel package for the existing Limen 3PL system
- Not a SaaS in v1
- Designed from day one for future SaaS compatibility
- Generic package core; host app owns business logic via Tools, Skills, Services

## Non-Negotiable Principle

The LLM must **never** directly control the application.

| LLM may | Laravel must |
|--------|----------------|
| Understand requests | Authenticate users |
| Suggest tools + arguments | Authorize actions |
| Generate responses | Validate input |
| Reason over allowed context | Execute tools safely |
| | Enforce business rules |
| | Scope data access |
| | Audit and log |

## Subsystems

1. Agents
2. Runtime
3. Tools
4. Skills
5. Workflows
6. Memory
7. Knowledge / RAG
8. Conversations
9. Authorization
10. Human Approval
11. Providers (LLM / Embeddings)
12. HTTP Integrations
13. Queue
14. Broadcasting
15. Chat UI + Themes
16. Attachments
17. Observability
18. Audit
19. Security
20. Artisan CLI + Stubs
21. Testing + Architecture Validation

## Agent Definition

An Agent is more than a prompt. It includes:

- Identity, name, description
- Model + instructions
- Skills, tools, knowledge, memory
- Permissions / policies
- Context + output configuration
- Limits + runtime configuration
- Version metadata

## Repository Abstraction

Runtime must not depend on config files directly.

Initial implementation:

- `ConfigAgentRepository`
- `ConfigToolRepository`
- `ConfigSkillRepository`
- `ConfigWorkflowRepository`
- `ConfigKnowledgeRepository`

Future:

- Database-backed repositories for SaaS

## First Demonstrations

### Demo 1 — Shipment lookup

```
User: "Where is shipment 12345?"
→ Auth → Authorize agent → LLM selects tool
→ Authorize tool → Validate → Execute Limen service
→ Return result → Generate response → Broadcast → UI
```

### Demo 2 — Delayed shipment with approval

```
Detect delay → Draft customer message → Request approval
→ User approves → Send message → Audit
```

## Definition of Done

A feature is complete only when it includes:

- Code
- Tests
- Documentation
- Configuration
- Error handling
- Security consideration
- Events (where appropriate)
- Integration consideration
- Architecture validation
- Updated checklist

## Implementation Rule

Do **not** implement the entire package in one task. Work phase-by-phase using:

- [ROADMAP.md](ROADMAP.md)
- [IMPLEMENTATION.md](IMPLEMENTATION.md)
- [STATUS.md](STATUS.md)
- [.ai/MASTER_PROMPT.md](.ai/MASTER_PROMPT.md)

## Checklist — Phase 01 (Architecture)

- [x] Analyze specification
- [x] Identify missing architectural decisions
- [x] Produce architecture proposal
- [x] Produce package directory structure
- [x] Produce contracts (interfaces)
- [x] Produce dependency graph
- [x] Produce implementation roadmap
- [x] Produce database proposal
- [x] Produce event map
- [x] Produce security model
- [x] Produce testing strategy
- [x] Produce configuration schema
- [x] Produce Artisan command map
- [x] Produce Blade/UI architecture
- [x] Produce SaaS migration strategy
- [x] Document contradictions / ambiguities
- [x] Create project documentation files
- [x] Architecture approval
- [x] Begin Phase 02 implementation
- [x] Phase 02 — DTOs, config repositories, bindings, tests
- [x] Begin Phase 03 — Provider System
- [x] Phase 03 — Fake/OpenAI providers, managers, tests
- [x] Begin Phase 04 — Agent System
- [x] Phase 04 — Agent resolver, tool schemas, validation command
- [x] Begin Phase 05 — Tool System
- [x] Phase 05 — Tool pipeline, validation, audit, idempotency
- [x] Begin Phase 06 — Runtime
- [x] Phase 06 — Core runtime loop, limits, run state skeleton
- [x] Begin Phase 07 — Conversations
- [x] Phase 07 — Conversation/message persistence, history loading, migrations
- [x] Begin Phase 08 — Auth & Authorization
- [x] Phase 08 — Gate/policy integration, guest sessions, context validation
- [x] Begin Phase 09 — State & Checkpoints
- [x] Phase 09 — Run/checkpoint/approval persistence, resume/reject/cancel hardening
- [x] Begin Phase 10 — Memory
- [x] Phase 10 — Scoped memory stores, retrieval hooks, runtime integration
- [x] Begin Phase 11 — Knowledge / RAG
- [x] Phase 11 — Config/vector retrievers, vector store abstraction, runtime integration
