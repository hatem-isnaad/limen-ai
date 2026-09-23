# Limen AI — Security Model

## Threat Model Summary

Limen AI sits between untrusted users, an untrusted LLM, and trusted Laravel business logic. The framework must assume:

- Users may attempt privilege escalation via prompts
- LLM output may be malformed or malicious
- External content (RAG, attachments, HTTP responses) is untrusted
- Tool arguments from the LLM are untrusted until validated

## Security Layers

```
Request Authentication (Laravel Guards)
        ↓
Agent Authorization (abilities/policies)
        ↓
Context Building (server-derived user/tenant scope)
        ↓
LLM Call (no secrets in prompt)
        ↓
Tool Authorization (per tool, per user)
        ↓
Input Validation
        ↓
Approval Gate (sensitive tools)
        ↓
Idempotency + Rate Limits
        ↓
Execution (host services / bounded HTTP)
        ↓
Output Redaction + Audit
```

## Non-Trust List

Never trust from LLM output:

- `user_id`
- `organization_id` / `tenant_id`
- Permission decisions
- Ownership of records
- Raw SQL/query instructions
- URLs for server-side fetch (without SSRF checks)

## Authentication

- Use Laravel guards and session/token auth from host app
- Package exposes helpers: `isAuthenticated()`, `currentUser()`, `isGuestAllowed()`
- Guest access is explicit per agent config

## Authorization

- Agent-level abilities (e.g. `support.use`)
- Tool-level abilities (e.g. `shipments.read`)
- Laravel Gates/Policies integration
- Data scoping enforced in host services, not in LLM

## Tool Security

- Authorization before execution
- Confirmation for destructive/sensitive operations
- Configurable argument/result redaction in logs
- Timeout and rate limits per tool
- Idempotency keys for side-effect tools

## External HTTP Security

- Allowlist/blocklist for outbound domains
- Block private IP ranges (SSRF)
- Validate redirects
- Never pass credentials to LLM
- Store secrets in env/config, resolved at runtime

## Attachments

- Upload size, mime type, and per-conversation count limits are enforced server-side
- Extracted attachment text is treated as untrusted and wrapped before LLM injection
- Optional vector RAG over attachments uses the same sanitization rules as knowledge retrieval
- Files are stored on the configured disk (`attachments.disk`) or in-memory for development
- Use `DatabaseAttachmentStore` in production with the `limen_ai_attachments` migration

## Prompt Injection Mitigation

- Separate system/instruction from untrusted content
- Mark retrieved knowledge, attachments, and user content as untrusted
- Optional sanitization filters
- Tool allowlists per agent (LLM cannot invoke undeclared tools)

## Broadcasting Security

- Private channels only
- Channel authorization verifies conversation access
- Never expose API keys to browser (use Laravel Echo + auth endpoint)

## Audit

Log at minimum:

- User, agent, tool, timestamp
- Authorization result
- Approval result
- Success/failure (redacted arguments)

## Secrets Management

- Provider API keys in `.env`
- HTTP connector credentials in encrypted config or env references
- No secrets in conversation history sent to LLM

## Failure Mode

When in doubt: **deny execution**, return safe user-facing message, log internally.

## Guest mode (public widgets)

When `LIMEN_AI_UI_GUEST_ENABLED=true` and `LIMEN_AI_UI_REQUIRE_AUTH=false`, unauthenticated visitors can start conversations. Treat this as a **public attack surface**.

**Minimum hardening:**

- Keep `guest_allowed` **false** on agents that can call privileged tools unless you add separate guest-safe agents
- Use `CacheGuestSessionValidator` (default) so guest tokens expire and cannot be reused indefinitely
- Guest/public widgets auto-apply `ThrottleAgentRequests` when `LIMEN_AI_UI_GUEST_ENABLED=true` (optional `LIMEN_AI_UI_RATE_LIMIT_ENABLED=true` for authenticated routes)
- Mark read-only widget tools with `guest_safe: true`; `limen-ai:validate` fails guest agents with unsafe tools
- Disable tools that mutate data, send messages, or reach internal APIs for guest-facing agents
- Run `php artisan limen-ai:doctor` — in-memory persistence with UI enabled is reported as a failure
- Run `php artisan migrate` so persistence auto-detects database (or set `LIMEN_AI_PERSISTENCE_DRIVER=database`)

**Do not:**

- Expose admin or internal agents on public pages
- Pass tenant or user identifiers from the browser into run context
- Disable authorization gates globally to “make the widget work”

## Pre-release security checklist (v1.0.0+)

Before tagging a release, confirm:

- [ ] `composer test:security` passes (SSRF, sanitization, redaction matrix)
- [ ] `composer test:architecture` passes (no host `App\` imports in package)
- [ ] Tool pipeline authorization tests pass for guest and authenticated users
- [ ] HTTP integration tools block private IPs and disallowed redirects
- [ ] Provider and connector secrets are env/config only — never in prompts or logs
- [ ] Approval gates remain enabled for sensitive tools (e.g. customer messaging)
- [ ] Broadcasting uses private channels with conversation access checks
- [ ] Default agent config uses fake provider in examples; production uses real keys in `.env`

See [docs/release.md](docs/release.md) for the full release process.
