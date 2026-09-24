# Limen AI — Architectural Decisions

## ADR-001 — Package-first, not SaaS-first

**Status:** Accepted

Build as installable Laravel package with config-driven definitions. SaaS abstractions (database repositories, tenants) are prepared but not implemented in v1.

## ADR-002 — LLM proposes, Laravel disposes

**Status:** Accepted

All tool execution passes through Laravel authorization, validation, and business services. LLM output is untrusted input.

## ADR-003 — Repository abstraction for definitions

**Status:** Accepted

Agent/Tool/Skill/Workflow/Knowledge definitions are accessed via repository contracts. Phase 1 uses config repositories only.

## ADR-004 — Modular runtime, no god Agent class

**Status:** Accepted

Execution split across Runtime, ContextBuilder, ToolPipeline, ApprovalService, CheckpointManager, etc.

## ADR-005 — Provider adapter pattern

**Status:** Accepted

LLM vendors implemented behind `LlmProvider` contract. OpenAI first in Phase 03; Anthropic/Gemini/OpenRouter follow.

## ADR-006 — Pusher as first broadcast adapter

**Status:** Accepted

Broadcasting via `RealtimeBroadcaster` contract. Pusher is default adapter; Reverb added later without runtime changes.

## ADR-007 — Queue-based long execution

**Status:** Accepted

Agent runs dispatched as queue jobs. HTTP initiates run; broadcasting delivers updates.

## ADR-008 — Declarative HTTP tools

**Status:** Accepted

External API tools use config mappings, not arbitrary code execution from LLM or untrusted config.

## ADR-009 — Host app owns business tools

**Status:** Accepted

Limen shipment/order tools live in host app and call existing Laravel services directly.

## ADR-010 — Localization from day one

**Status:** Accepted

Framework messages use Laravel lang files with English and Arabic.

## Open Questions (Need Confirmation)

### OQ-001 — Vector store default

**Options:** pgvector, Meilisearch, Pinecone, Redis, pluggable none-default

**Recommendation:** No default implementation locked in Phase 01. Contract + null driver for tests.

### OQ-002 — JSON Schema library

**Options:** `opis/json-schema`, `swaggest/php-json-schema`, Laravel-only validation

**Recommendation:** Laravel Validator for v1 tool input; evaluate JSON Schema in Phase 05.

### OQ-003 — Streaming protocol

**Options:** SSE, chunked JSON, WebSocket-only via Pusher

**Recommendation:** SSE for HTTP + Pusher events for UI (decide in Phase 15).

### OQ-004 — Package publish name

**Current:** `limen-ai/limen-ai`

**Alternative:** `isnaad/limen-ai` for Packagist org branding.

### OQ-005 — Conversation storage driver

**Recommendation:** Eloquent/DB default for conversations/runs; config-only for agent definitions in v1.

## Contradictions / Clarifications Resolved

| Topic | Resolution |
|-------|------------|
| Large architecture vs phased delivery | Design comprehensively, implement in phases |
| Database for definitions vs config | Config repos in v1; DB repos for SaaS later |
| Generic vs Limen-specific | Generic package; Limen tools in host app |
