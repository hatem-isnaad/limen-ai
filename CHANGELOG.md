# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2026-09-23

Host integration release: production-ready web chat persistence, guest sessions, output validation hooks, and chat UI hardening.

### Fixed

- Persistence auto-detects `database` when `limen_ai_conversations` exists (`LIMEN_AI_PERSISTENCE_AUTO_DETECT`, default true)
- `LIMEN_AI_PERSISTENCE_DRIVER=database` switches all repos to database implementations (fixes web chat `403` on second request)
- Widget and chatbot apply `LIMEN_AI_UI_*` theme env vars over agent persona UI and presets
- Conversation sync persists only final assistant text; tool JSON hidden from user-visible history
- CLI `limen-ai:run` and `limen-ai:agent:test` authenticate via `Auth::loginUsingId()`
- Release test blockers: checkpoint FK seeding, guest validator alignment, runtime history persistence

### Added

- `PersistenceConfig`, `EnvironmentDoctor`, and `limen-ai:doctor` persistence checks
- Guest sessions API, `CacheGuestSessionValidator`, and guest hardening guide in `SECURITY.md`
- `OutputValidator` / `OutputModerator` contracts with `StructuredOutputValidator` and `BasicOutputModerator`
- `limen-ai:skill:test` and `limen-ai:agent:test --expect-contains`
- Skill metrics in audit logs; published UI `VERSION` stamp and stale-view doctor warning
- Host integration test template, Ollama guide, synced env templates, and `docs/HOST-INTEGRATION-AUDIT.md`
- Chat UI: history drawer, bilingual AR/EN, resume-last-conversation, theme/i18n env controls

## [1.0.0] - 2026-09-23

First stable release of the Limen AI Laravel agent framework (phases 01–22).

### Added

- Config-driven agents, tools, skills, workflows, and knowledge collections
- LLM and embedding provider managers with fake drivers for testing
- Multi-step agent runtime with tool pipeline, approvals, checkpoints, and limits
- Authorization integration (Gates/policies), guest session validation, and run context checks
- Conversation, run, checkpoint, and approval persistence (in-memory and database drivers)
- Scoped memory retrieval and RAG knowledge injection into runtime context
- Workflow engine with agent, tool, approval, and branch steps
- Declarative HTTP integrations with SSRF validation and secret resolution
- Security hardening: DNS-aware SSRF, redirect blocking, prompt-injection sanitization, redaction
- Queue-dispatched agent runs and Pusher-compatible realtime broadcasting
- Blade chat UI (chatbot/widget), HTTP API, and Echo-ready JS client
- Theme presets with RTL/LTR and light/dark mode support
- Observability: trace correlation, usage tracking, audit export, run report API
- Artisan developer tools: `limen-ai:doctor`, `limen-ai:list`, `make:agent`, `make:tool`, `make:skill`
- Architecture boundary tests, security critical matrix, and GitHub Actions CI matrix
- Limen 3PL host reference integration with shipment lookup and approval-gated messaging
- Request-scoped agent resolution cache and tool schema memoization (`performance.cache_resolved_agents`)
- Release documentation (`docs/release.md`, `docs/performance.md`) and v1.0.0 release workflow

### Security

- Tool authorization enforced before execution; sensitive tools require confirmation/approval
- Untrusted content (user messages, RAG, memory) sanitized before LLM calls
- Outbound HTTP restricted by allowlists and private-IP blocking
- See [SECURITY.md](SECURITY.md) for the full threat model

[1.0.0]: https://github.com/hatem-isnaad/limen-ai/releases/tag/v1.0.0
