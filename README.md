# Limen AI

**Limen AI** is a reusable Laravel AI Agent Framework. It provides agents, tools, skills, workflows, memory, knowledge retrieval, chat UI, and observability — while keeping business logic, authorization, and data access in the host application.

**Current release:** v3.3.0

## Core principle

The LLM proposes actions. Laravel decides whether they are allowed and executes them safely.

## Features

- **Multi-provider LLM support** — OpenAI, Anthropic, Gemini, OpenRouter, and custom drivers from config
- Per-agent `provider` + `model` selection (no code changes to switch models)
- **Class-based agents** (Laravel AI SDK style: `Promptable`, `make:agent`) plus config-driven agents (v1 compatible)
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
php artisan limen-ai:install --with-ui --with-example
php artisan migrate
php artisan limen-ai:doctor
```

Copy variables from `.env.limen-ai.example` into `.env`, then set your provider (`LIMEN_AI_PROVIDER=openai`) and API keys.

See [docs/release.md](docs/release.md) for full publish tags, validation, and upgrade notes.

## Quick start

1. Register business logic in `App\Providers\LimenAiHostServiceProvider` or config fragments under `config/limen-ai/`.
2. Generate a tool and auto-register it:

```bash
php artisan limen-ai:make:tool LookupOrder --register
php artisan limen-ai:make:knowledge support --faq
```

3. Run agents via facade or SDK-style classes:

```php
use App\Ai\Agents\SupportAgent;
use LimenAi\Facades\LimenAi;

LimenAi::faq('support', 'What are your hours?', '9am-5pm UTC');

// Config agent (1.x)
$runId = LimenAi::run('example', $conversationId, 'Hello');

// Class agent (2.x) — register in config limen-ai.agent_classes or LimenAi::agent()
$response = SupportAgent::make()->prompt('Hello');
```

See [docs/UPGRADE-2.0.md](docs/UPGRADE-2.0.md) for migration notes.

4. Embed the chat component:

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
| [docs/release.md](docs/release.md) | Install, publish, and release |
| [docs/api-custom-frontend.md](docs/api-custom-frontend.md) | HTTP/SSE API for your own UI |
| [docs/LARAVEL-AI-SDK-PARITY.md](docs/LARAVEL-AI-SDK-PARITY.md) | Laravel AI SDK capability matrix (v3+) |
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
