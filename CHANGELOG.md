# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-09-23

### Changed

- feat: ship host integration improvements and fix release test blockers (79aee2a)
## [Unreleased]

### Fixed

- Widget and chatbot now correctly apply `LIMEN_AI_UI_*` theme env vars over agent persona UI and theme presets (`ThemeResolver`, `WidgetThemeOptions`, `widget` Blade component)
- Removed hardcoded example-agent `persona.ui` copy that masked env-driven widget titles and welcome messages
- `LIMEN_AI_PERSISTENCE_DRIVER=database` switches conversation, message, run, checkpoint, and approval repos to database implementations (fixes web chat `403` on second request)
- `limen-ai:doctor` warns on in-memory persistence (fails in production) and missing migrations when database driver is enabled
- CLI `limen-ai:run` and `limen-ai:agent:test` authenticate via `Auth::loginUsingId()` before agent execution
- Conversation sync after agent runs persists only final assistant text (not tool JSON or tool-call stubs) and accounts for runtime context prefixes (memory, knowledge, persona)
- Chat API and widget filter internal `tool` role messages from user-visible history
- Release test fixes: checkpoint FK seeding in `DatabaseCheckpointStoreTest`, guest validator binding/auth alignment, `AgentRuntimeTest` history persistence

### Added

- `OutputValidator` and `OutputModerator` contracts with `StructuredOutputValidator`, `BasicOutputModerator`, and chained validation in `AgentResponseGuard`
- `limen-ai:skill:test` command and `limen-ai:agent:test --expect-contains` smoke assertion flag
- Skill adherence metrics (`skill_keys`, `skill_count`) in audit logs for agent start/complete events
- Published UI `VERSION` stamp and `limen-ai:doctor` warning when host views are stale
- Host integration test template at `examples/limen-host/tests/Feature/LimenAiAgentTest.php`
- Synced `.env.example` and `stubs/limen-ai.env.example` with all `LIMEN_AI_*` config keys
- `DatabaseConversationRepository` and `DatabaseMessageRepository` tests; HTTP persistence feature test
- `PersistenceConfig` and `EnvironmentDoctor` support classes
- Ollama/local LLM setup guide in `docs/providers.md`
- Installation guide section: web UI requires database persistence
- Professional chat UI redesign: avatars, typing indicator, animations, sounds, unread badge, polished widget launcher
- Configurable UI preferences (`ui.sounds`, `ui.animations`, `ui.widget`, `ui.messages`, `ui.composer`) via config and env
- [`AGENTS.md`](AGENTS.md) — complete AI agent and contributor reference guide
- [`.ai/REFERENCE.md`](.ai/REFERENCE.md) — extended technical reference for AI coding sessions
- Laravel 13 support (`illuminate/* ^13.0`, `orchestra/testbench ^11.0`)
- [docs/installation.md](docs/installation.md) — install via Packagist, VCS, path repo, private registry, or monorepo
- Organized documentation into `docs/architecture/`, `docs/project/`, and `docs/development/`
- Full attachment pipeline: upload API, validation, text extraction, runtime injection, optional vector RAG
- `limen-ai:install` and full Artisan CLI surface (`run`, `agent:test`, `tool:test`, `workflow:test`, `logs`, inspection and generator commands)
- `LimenAi` facade and `LimenAiManager` for programmatic agent/workflow execution
- `DatabaseAttachmentStore` with `limen_ai_attachments` migration
- Example agents for each built-in LLM provider: `example_openai`, `example_anthropic`, `example_gemini`, `example_openrouter` (plus `example` for fake)
- `.env.example` and publishable `limen-ai-env` tag (`.env.limen-ai.example`) with all Limen AI configuration keys
- Configurable agent persona: display name, tone, language (`auto` follows request locale), response style, custom rules, and forbidden topics
- `AgentPersonaComposer` injects persona into system instructions; global `quality.save_tokens` adds concise-response guidance
- `StrictMemoryPolicy` with key allowlists, regex validation, and value truncation; throws `MemoryPolicyException` on violations
- `AgentResponseGuard` enforces per-agent `output.max_response_chars` on final assistant replies
- Per-agent `limits.max_history_messages`, `limits.temperature`, and memory `allowed_keys` / `max_value_length`
- `docs/agent-configuration.md` — full guide for persona, memory, token control, and security layers
- First-class `AnthropicProvider`, `GeminiProvider`, and OpenRouter (OpenAI-compatible) LLM drivers
- `OpenAiEmbeddingProvider` for production vector knowledge
- Config-driven provider registry (`providers.drivers`) for adding custom LLM adapters without core changes
- `docs/providers.md` and publishable `custom-llm-provider.stub`
- Agent validation for registered provider drivers

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

[1.5.0]: https://github.com/hatem-isnaad/limen-ai/releases/tag/v1.5.0
