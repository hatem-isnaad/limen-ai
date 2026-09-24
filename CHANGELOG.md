# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.3.2] - 2026-09-24

### Added

- OpenAI-compatible **streaming tool-call delta** assembly (`OpenAiStreamingToolCallAssembler`)
- Live stream tool-loop steps when `streaming.allow_with_tools` is enabled (text deltas + tool-call/result meta)

### Fixed

- `FakeLlmProvider` stream preserves whitespace without spurious trailing spaces on sync `run()`

## [3.3.1] - 2026-09-24

### Fixed

- Package config **deep-merge** for `tools`, `agents`, `skills`, and `workflows` so Laravel `config/limen-ai/{section}/*.php` fragments no longer replace entire package sections (restores `example_http_status` and similar defaults in tests and host apps)
- **Memory** and **knowledge** container bindings resolve driver/store from config at resolve time (supports runtime `config()->set()` in tests)
- **Null knowledge driver** skips agent knowledge injection
- Test harness: stale Testbench tool fragments cleanup, `limen_3pl` enabled only in Limen/workflow integration tests, list command assertions via `Artisan::output()`

## [3.3.0] - 2026-09-24

### Added

- Groq and xAI LLM drivers (OpenAI-compatible APIs)
- Voyage document reranker; ElevenLabs speech-to-text SDK path
- Gemini Google Search grounding web search driver
- MCP **SSE** transport (`transport` => `sse`)
- Live provider streaming in tool-loop steps when tools are omitted/disabled
- Feature tests for Vercel + AG-UI protocol routes

### Fixed

- Architecture critical coverage data providers (PHPUnit 11)
- Release readiness version check (semver)
- Package boundary scan for host `App\` namespace defaults in make commands
- Risky Pusher boundary test (asserts scan ran)

## [3.2.1] - 2026-09-24

### Added

- Bedrock **converse-stream** support with AWS event-stream parsing
- Unit tests for Bedrock streaming

## [3.2.0] - 2026-09-24

### Added

- Vercel AI UI message stream encoder and full `VercelChatController` runtime integration
- AG-UI event encoder (`RUN_*`, `TEXT_*`, `TOOL_*`) with usage on `RUN_FINISHED`
- Streaming tool-loop events (`tool-call`, `tool-result`) via `executeLoopStreaming`
- Agent step middleware applied to non-tool `stream()` path
- AWS Bedrock Converse LLM provider; ElevenLabs TTS; Gemini Imagen image SDK path
- MCP HTTP + stdio transports, `McpClient`, auto-registration of remote MCP tools
- Web search drivers: OpenAI Responses + Anthropic native web search
- `DeferredToolsUntilSecondStepMiddleware` example
- `FileClient::upload()` alias

## [3.1.0] - 2026-09-24

### Added

- `AgentStepRunner` pipeline for agent step middleware (wired into `DefaultAgentRuntime`)
- `LimitToolCallsAfterFirstStepMiddleware` example
- Cohere and Jina document rerankers; `Ai::rerank()` selects via `LIMEN_AI_RERANK_PROVIDER`
- SDK-style `Ai::files()` and `Ai::vectorStore()` clients (Limen Knowledge backend)
- Provider tools: `WebSearchTool`, `WebFetchTool`, `FileSearchTool` (`LIMEN_AI_PROVIDER_TOOLS`)
- Optional AG-UI chat route (`LIMEN_AI_AG_UI`, `POST .../ag-ui`)
- Optional LLM conversation summarizer (`LIMEN_AI_LLM_SUMMARIZER`)
- Message API attachments → multipart user messages; `MessageFormatter` array content support
- `ResolvedAgent::chat()` supports step options (`omit_tools`, overrides)

## [3.0.0] - 2026-09-24

### Added

- Laravel AI SDK–style toolkit: `Ai` facade / `ai()` helper (images, audio, STT, embeddings, rerank, classify, anonymous agents)
- Provider failover chain (`LIMEN_AI_FAILOVER`)
- `Conversational::messages()` merged into agent history
- Agent step middleware hook (`agent_middleware` config)
- Tool approval **argument editing** via `tool_input` on approve
- Optional Vercel chat route (`LIMEN_AI_VERCEL_CHAT`, `POST .../chat`)
- `SubAgentTool`, `McpBridgeTool`, deferred tool loading flag
- `make:agent --structured` stub
- Parity matrix: [docs/LARAVEL-AI-SDK-PARITY.md](docs/LARAVEL-AI-SDK-PARITY.md)

## [2.7.0] - 2026-09-24

### Added

- `DatabaseConversationRepository` and `DatabaseMessageRepository`
- `LIMEN_AI_DB_PERSISTENCE=true` switches conversations, messages, runs, checkpoints, and approvals to database drivers
- Usage rows and in-memory records link to `conversation_id` + assistant `message_id` after each reply
- SSE stream final event includes `run_id` and `usage` (tokens + provider + model)

### Fixed

- Repository bindings now read config at resolve time (respects test/app overrides)

## [2.6.0] - 2026-09-24

### Added

- Per-reply `usage` on stored assistant messages (`provider`, `model`, `input_tokens`, `output_tokens`, `total_tokens`, `run_id`)
- Synchronous `POST /conversations/{id}/messages` returns `usage` when the run completes inline
- `GET /conversations/{id}` exposes `usage` on each assistant message
- Webhook `AgentCompleted` payload includes `usage` when available

## [2.5.0] - 2026-09-24

### Added

- Database table `limen_ai_usage_records` and `PersistingUsageTracker` (`LIMEN_AI_USAGE_PERSIST_DB`)
- `RunUsageFinalizer` — persists `usage_summary` on completed runs; estimates tokens when providers omit usage (streaming)
- `GET /runs/{id}` returns `usage_summary` and `usage_records`
- Optional outbound webhooks for `AgentCompleted` / `AgentFailed` (`LIMEN_AI_WEBHOOKS_ENABLED`, `LIMEN_AI_WEBHOOK_URLS`)

### Changed

- LLM usage rows are recorded only when reported `total_tokens` > 0 (avoids empty rows before estimation)

## [2.4.0] - 2026-09-24

### Added

- API routes decoupled from bundled UI (`LIMEN_AI_API_ENABLED`, works with `LIMEN_AI_UI_ENABLED=false`)
- `GET /health`, `GET /agents`, `GET /agents/{key}` catalog endpoints
- Configurable `api.middleware` / `api.route_prefix` (Sanctum-ready)
- Streaming with tools when `LIMEN_AI_STREAMING_WITH_TOOLS=true` (tool loop + streamed final text)

### Changed

- Install creates `app/Ai/Agents` and `app/Ai/Tools`; documents API-only setup

## [2.3.0] - 2026-09-24

### Added

- Anthropic and Gemini `StreamingLlmProvider` implementations
- REST CRUD for DB agent definitions (`/agent-definitions`) for custom admin UIs
- `AgentDefinitionStore` with cache invalidation and automatic version bump on update
- `Promptable::queue()` for async runs
- [docs/api-custom-frontend.md](docs/api-custom-frontend.md) — HTTP/API guide without bundled UI

### Changed

- `run()` persists `structured_output` on completed runs (JSON agents)
- `GET /runs/{id}` exposes `structured_output`
- Default tool generator path/namespace: `App\Ai\Tools`

## [2.2.0] - 2026-09-24

### Added

- LLM streaming via `StreamingLlmProvider`, `AgentRuntime::stream()`, and `LimenAi::stream()`
- `Promptable::stream()` for class-based agents
- SSE API: `POST /{prefix}/conversations/{id}/messages/stream` for custom frontends (no bundled UI)
- `AgentStreamDelta` event + Pusher broadcast hook for realtime custom UIs
- Structured JSON output helpers (`StructuredOutput`) wired into `ResolvedAgent` chat options (OpenAI `json_schema`)

### Changed

- `FakeLlmProvider` and `OpenAiProvider` implement streaming

## [2.1.0] - 2026-09-24

### Added

- Database-backed agent definitions (`limen_ai_agent_definitions` migration + `AgentDefinitionModel`)
- `DatabaseAgentRepository` and `CompositeAgentRepository` (default) with `agent_storage.definition_sources` priority
- `limen-ai:agents:import-config` and `limen-ai:agents:clear-cache` Artisan commands
- Doctor checks when `LIMEN_AI_DB_AGENTS` storage is enabled

### Changed

- Default `repositories.agent` binding is `CompositeAgentRepository` (config wins over DB for duplicate keys by default)

## [2.0.0] - 2026-09-24

### Added

- Laravel AI SDK–style agent layer under `LimenAi\Ai\` (`Agent`, `Promptable`, `HasTools`, `HasStructuredOutput`, `Message`, `AgentResponse`)
- Class-based agents via `agent_classes` config, `LimenAi::agent($key, Class::class)`, and `ClassAgentDefinitionFactory`
- `make:agent` Artisan alias generating PHP agent classes by default (`--config` for legacy stubs)
- `RuntimeToolCatalog` so tools declared on class agents resolve without duplicate config
- PHP attributes `UsesModel` and `UsesProvider` for class agent defaults
- Upgrade guide: [docs/UPGRADE-2.0.md](docs/UPGRADE-2.0.md)

### Changed

- `limen-ai:make:agent` now scaffolds class agents (Laravel AI SDK style); use `--config` for v1 config fragments
- Default agent class paths: `app/Ai/Agents` and namespace `App\Ai\Agents`

### Deprecated

- Nothing removed in 2.0; config-defined agents remain first-class.

## [1.1.x]

### Added

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
