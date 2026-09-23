# Limen AI

**Limen AI** is a reusable Laravel AI Agent Framework. It provides agents, tools, skills, workflows, memory, knowledge retrieval, chat UI, and observability — while keeping business logic, authorization, and data access in the host application.

**Current release:** v1.0.0

## Core principle

The LLM proposes actions. Laravel decides whether they are allowed and executes them safely.

## Features

- **Multi-provider LLM support** — OpenAI, Anthropic, Gemini, OpenRouter, and custom drivers from config
- **Per-agent persona controls** — display name, tone, language, response style, rules, and forbidden topics from config
- **Strict memory policy** — key allowlists, length limits, and sanitization on store and recall
- **Token and quality guards** — temperature, history limits, max tokens, and hard response length caps
- Per-agent `provider` + `model` selection (no code changes to switch models)
- Config-driven agents, tools, skills, and workflows
- Multi-step runtime with tool pipeline, approvals, and checkpoints
- SSRF protection, prompt-injection sanitization, and audit logging
- Conversation persistence, scoped memory, and RAG knowledge injection
- Queue-dispatched runs and Pusher-compatible broadcasting
- Blade chat UI with theming (RTL/LTR, light/dark)
- Artisan generators (`make:agent`, `make:tool`, `make:skill`) and `limen-ai:doctor`
- Reference Limen 3PL host integration with approval-gated messaging

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0

## Installation

```bash
composer require limen-ai/limen-ai
php artisan vendor:publish --tag=limen-ai-config
php artisan migrate
php artisan limen-ai:doctor
```

See [docs/release.md](docs/release.md) for full publish tags, validation, and upgrade notes.

## Quick start

1. Configure an agent in `config/limen-ai.php` (an `example` agent ships with the package).
2. Implement a tool in `App\LimenAi\Tools\` and register it in config.
3. Run a conversation via the HTTP API or embed the chat component:

```blade
<x-limen-ai::chatbot agent="example" />
```

4. Use `FakeLlmProvider` in tests — no real LLM calls required.

## Documentation

| Document | Description |
|----------|-------------|
| [AI_SPEC.md](AI_SPEC.md) | Master specification |
| [ARCHITECTURE.md](ARCHITECTURE.md) | System architecture |
| [SECURITY.md](SECURITY.md) | Threat model and controls |
| [TESTING.md](TESTING.md) | Test strategy |
| [docs/providers.md](docs/providers.md) | LLM/embedding providers and models |
| [docs/agent-configuration.md](docs/agent-configuration.md) | Persona, tone, language, memory, and quality |
| [docs/release.md](docs/release.md) | Install, publish, and release |
| [docs/limen-integration.md](docs/limen-integration.md) | Limen 3PL host demo |
| [docs/ci.md](docs/ci.md) | CI matrix and merge gates |
| [CHANGELOG.md](CHANGELOG.md) | Version history |

## First host application

The first integration target is **Limen**, a 3PL/fulfillment management system. The package remains domain-agnostic; Limen-specific tools live in the host app under `App\LimenAi\Tools\`.

## Development

```bash
composer install
composer test
composer test:gates   # architecture + security merge gates
```

## License

MIT
