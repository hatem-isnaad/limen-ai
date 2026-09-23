# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- `docs/index.html` — synced to v1.2.2 (quality layer, full HTTP API, Ollama, streaming, releases)

## [1.2.2] - 2026-09-23

### Added

- `LlmJudgeOutputValidator` — optional semantic scoring via `LIMEN_AI_SEMANTIC_VALIDATION=true` (works with Ollama/OpenAI-compatible providers)
- Auto-applied `ThrottleAgentRequests` for guest/public widgets (no middleware publish required)

### Changed

- `docs/agent-configuration.md` and `docs/providers.md` — Ollama `qwen3:8b` quality guidance
- Install next-steps updated for semantic validation and automatic guest rate limiting
- `.env.example` and `stubs/limen-ai.env.example` — semantic validation, guest auto-throttle, Ollama `qwen3:8b` notes

## [1.2.1] - 2026-09-23

### Fixed

- CI matrix excludes PHP 8.2 × Laravel 13 (Laravel 13 requires PHP ^8.3)

### Added

- `laravel/pint` dev dependency and `composer test:style` gate in `test:release`
- `.github/scripts/prune-stray-tags.sh` to remove experimental tags newer than `composer.json` version

### Changed

- Release script resolves previous tag from semver (ignores stray `v1.3+` experimental tags)
- Pint formatting applied across `src/`, `tests/`, and `config/`
- Removed stray experimental tags `v1.3.0`–`v1.7.1` from origin

## [1.2.0] - 2026-09-23

### Added

- `LlmConversationSummarizer` — optional LLM-backed long-thread compression with metadata cache
- `GET /runs/{id}/stream` — SSE endpoint for run status, delta chunks, and completion events
- Chat UI EventSource fallback when Laravel Echo is unavailable (`ui.streaming.enabled`)
- `LIMEN_AI_BROADCAST_DRIVER=reverb` alias for Laravel Reverb broadcasting
- Conversation history now keeps only `summary_keep_recent` messages when a summary is active
- [docs/project/ROADMAP-v1.2.md](docs/project/ROADMAP-v1.2.md)

### Changed

- Guest stream access supports `?guest_token=` query param (for EventSource clients)

## [1.1.0] - 2026-09-23

### Added

- `limen-ai:install --migrate` — optional migration step after publishing assets
- `limen-ai:checklist` — first-run host integration checklist
- `HeuristicOutputValidator` and `ForbiddenTopicsOutputValidator` (configurable quality layer)
- `SkillAdherenceReporter` for optional skill phrase checks in audit logs
- Skill-scoped tool filtering when skills define `tools`
- `ThrottleAgentRequests` middleware (`limen-ai-middleware` publish tag)
- `CompositeToolRepository` + `LimenAi::registerTool()` for runtime tool registration
- `delegate_to_agent` router tool and `router.delegates` config
- `limen-ai:agent:test --expect-not-contains` and `--min-length`
- `limen-ai:validate --strict` and persistence mode output
- `limen-ai:doctor --json` `ok` field for CI pipelines
- `tests/Feature/WebChatPersistenceE2ETest` — full HTTP persistence regression test
- [docs/QUALITY-GATE.md](docs/QUALITY-GATE.md)

### Changed

- Doctor fails (non-testing) when UI is enabled but persistence tables are missing
- Guest agents must only expose tools marked `guest_safe: true`
- Default tool repository is now `CompositeToolRepository`

## [1.0.7] - 2026-09-23

### Added

- `limen-ai:import:knowledge` — import FAQ documents from JSON or CSV into `config/limen-ai-knowledge.php` (merged on boot)
- `limen-ai:doctor --json` — machine-readable health report for CI and scripts
- `limen-ai:doctor` cloud provider probes — OpenAI, OpenRouter, Anthropic, and Gemini reachability checks (Ollama probe unchanged)
- Default black-box install defaults — `app_assistant` agent + `product_help` knowledge collection in package config and install publishables

### Changed

- `limen-ai:install` publishes `config/limen-ai-knowledge.php` stub and documents import/doctor/widget next steps
- Default `LIMEN_AI_DEFAULT_AGENT` is now `app_assistant` (package tests still pin `example`)

## [1.0.6] - 2026-09-23

### Added

- `limen-ai:doctor` Ollama probe — checks `/v1/models` reachability and warns when the configured model is not installed
- Expanded [black-box-host-guide.md](docs/black-box-host-guide.md) — full env → agent → KB → tools → widget path
- New [knowledge-base-setup.md](docs/knowledge-base-setup.md) — FAQ/RAG collections cookbook for developers
- Updated [README.md](README.md), [docs/index.html](docs/index.html), and [host-quickstart.md](docs/host-quickstart.md) for black-box developer onboarding

## [1.0.5] - 2026-09-23

### Fixed

- Aligned all tool stubs, examples, and docs with `BaseTool` + `authorize()` black-box pattern
- `ClassBasedToolExecutor` reuses `ToolInstanceResolver`; install command mentions auto-detect persistence
- `configuration-schema.md`, `AGENTS.md`, and `README.md` updated for simple auth mode and persistence auto-detect
- Added `LaravelAuthorizationService` test for simple mode tool access without Gates

## [1.0.4] - 2026-09-23

### Added

- **Black-box authorization mode** (`LIMEN_AI_AUTHORIZATION_MODE=simple`, default) — no Laravel Gates required for empty `authorization.abilities`
- `AuthorizableTool` contract and `BaseTool` host base class with `authorize($input, $context): bool` — return `false` to block execution
- `ToolInstanceAuthorizer` runs before tool `handle()` in the pipeline
- [docs/black-box-host-guide.md](docs/black-box-host-guide.md) — env + tool-only setup for host developers
- `LIMEN_AI_REQUIRE_AUTH` env (default `false`) for agent auth requirement

## [1.0.3] - 2026-09-23

### Added

- [docs/scaling-agents-and-tools.md](docs/scaling-agents-and-tools.md) — multi-agent layout, tool limits, verdict matrix
- [docs/host-quickstart.md](docs/host-quickstart.md) — install to working widget in ~15 minutes
- [examples/limen-host/config/multi-agent.example.php](examples/limen-host/config/multi-agent.example.php) — `app_assistant` / `support_agent` / `admin_agent` pattern
- `limen-ai:validate` warnings when agents exceed `quality.tool_count_warn` (default 15) or `tool_count_critical` (25)

## [1.0.2] - 2026-09-23

### Fixed

- Persistence auto-detects `database` when `limen_ai_conversations` exists (`LIMEN_AI_PERSISTENCE_AUTO_DETECT`, default true)
- `limen-ai:doctor` fails when migrations exist but persistence is forced to memory

### Added

- Guest mode hardening guide in `SECURITY.md`
- Semantic scoring documented as host-implemented via `OutputValidator` / `OutputModerator`
- Persistence auto-detect tests and doctor persistence checks

## [1.0.1] - 2026-09-23

Host integration release: production-ready web chat persistence, guest sessions, output validation hooks, and chat UI hardening.

### Fixed

- `LIMEN_AI_PERSISTENCE_DRIVER=database` switches all repos to database implementations (fixes web chat `403` on second request)
- Widget and chatbot apply `LIMEN_AI_UI_*` theme env vars over agent persona UI and presets
- Conversation sync persists only final assistant text; tool JSON hidden from user-visible history
- CLI `limen-ai:run` and `limen-ai:agent:test` authenticate via `Auth::loginUsingId()`
- Release test blockers: checkpoint FK seeding, guest validator alignment, runtime history persistence

### Added

- `PersistenceConfig`, `EnvironmentDoctor`, and `limen-ai:doctor` persistence checks
- Guest sessions API and `CacheGuestSessionValidator`
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
