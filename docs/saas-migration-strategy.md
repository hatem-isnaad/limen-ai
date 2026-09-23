# Future SaaS Migration Strategy

v1 is a **package**, not SaaS. This document describes how to evolve without rewriting the core.

## What Stays Stable

- Runtime public API
- Tool pipeline
- Event names and contracts
- Authorization model (extended, not replaced)
- Chat UI component API

## What Changes in SaaS

| v1 Package | SaaS |
|------------|------|
| ConfigAgentRepository | DatabaseAgentRepository |
| ConfigToolRepository | DatabaseToolRepository |
| Single app install | Multi-tenant organizations |
| Env-based API keys | Per-tenant encrypted credentials |
| Host app auth | Workspace auth + API keys |
| File-based docs | Agent/Tool builder UI |

## Abstractions Already Prepared

- Repository contracts for all definitions
- Version fields on agent/tool/skill/workflow definitions
- Optional `tenant_id` on operational tables
- Provider manager for multi-vendor support
- Usage records table for billing metrics

## Migration Path

### Step 1 — Dual repository mode

Support reading agents from config OR database with precedence rules.

### Step 2 — Tenant context

Introduce `TenantContext` resolved from subdomain/API key, injected into AuthorizationService.

### Step 3 — Builder UI

CRUD for agent/tool definitions stored in DB, compiled to runtime-compatible DTOs.

### Step 4 — Billing

Usage records → subscription limits → enforcement in Runtime limits service.

## Not in Scope Until Explicitly Requested

- Organization management UI
- Stripe/billing integration
- Public agent marketplace
- Customer self-service signup

## Design Guardrails for SaaS

1. Never store secrets in agent definition JSON visible to LLM
2. Tenant isolation at query level (global scopes)
3. Package core remains installable without SaaS modules
4. SaaS features live in optional modules or separate package (`limen-ai/saas`)
