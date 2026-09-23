# Limen AI

**Limen AI** is a production-ready Laravel package for building AI agents — with tools, skills, workflows, memory, knowledge retrieval (RAG), approvals, chat UI, and observability built in.

> **Current release:** [v1.3.0](https://github.com/hatem-isnaad/limen-ai/releases/tag/v1.3.0)

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Documentation

| Resource | Description |
|----------|-------------|
| [docs/installation.md](docs/installation.md) | **Install guide** — Packagist, VCS, path repo, private registry, monorepo |
| [docs/index.html](docs/index.html) | Interactive documentation hub |
| [docs/README.md](docs/README.md) | Full markdown documentation index |

```bash
open docs/index.html
# or: php -S localhost:8080 -t docs
```

---

## Package structure

```
limen-ai/
├── config/limen-ai.php       # Default configuration
├── database/migrations/      # Optional persistence tables
├── docs/                     # All documentation
├── routes/limen-ai.php       # HTTP API routes
├── src/                      # Package source (LimenAi\ namespace)
├── resources/                # Blade views, CSS, JS
├── stubs/                    # Generator stubs + env template
├── tests/                    # Package test suite
├── examples/                 # Host app integration examples
├── README.md                 # This file
├── CHANGELOG.md
└── SECURITY.md
```

---

## Core principle

> **The LLM proposes actions. Laravel decides whether they are allowed and executes them safely.**

User identity, permissions, data scoping, and side effects never come from model output. They come from your authenticated Laravel context, Gates, and host application services.

---

## What you get

| Area | Capabilities |
|------|--------------|
| **Agents** | Config-driven definitions with per-agent provider, model, persona, limits, and authorization |
| **Tools** | Class-based Laravel tools or declarative HTTP integrations with validation and audit |
| **Skills** | Reusable instruction + tool bundles attached to agents |
| **Workflows** | Multi-step automations with agent steps, tool steps, branches, and approval gates |
| **Memory** | Conversation and user-scoped memory with strict key policies |
| **Knowledge (RAG)** | Config or vector-backed document retrieval injected into context |
| **Runtime** | Multi-turn loop with checkpoints, idempotency, and execution limits |
| **Security** | SSRF protection, prompt-injection sanitization, output redaction, audit logging |
| **UI** | Drop-in Blade chatbot and floating widget with RTL/LTR and light/dark themes |
| **API** | REST endpoints for custom frontends (React, Vue, mobile) |
| **Observability** | Trace IDs, token usage, and per-run audit trails |
| **Testing** | `FakeLlmProvider` for deterministic CI — zero LLM API spend |

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.2 |
| Laravel | ^11.0, ^12.0, or ^13.0 |

---

## Installation

Install into an **existing Laravel 11, 12, or 13 app**. You do not need Packagist — pick any method below.

**Full guide:** [docs/installation.md](docs/installation.md)

### Option A — Packagist (production)

```bash
composer require limen-ai/limen-ai
php artisan limen-ai:install
php artisan migrate
php artisan limen-ai:doctor
```

### Option B — Git repository (no Packagist)

Add to your host app `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/hatem-isnaad/limen-ai.git" }
    ],
    "require": { "limen-ai/limen-ai": "^1.0" }
}
```

```bash
composer update limen-ai/limen-ai
php artisan limen-ai:install
```

### Option C — Local path (development)

Clone next to your Laravel app and symlink via Composer:

```json
{
    "repositories": [
        { "type": "path", "url": "../limen-ai", "options": { "symlink": true } }
    ],
    "require": { "limen-ai/limen-ai": "@dev" }
}
```

### Option D — Private Composer registry

Point at your Satis or private Packagist URL — see [docs/installation.md](docs/installation.md#method-4--private-composer-registry).

### Post-install (all methods)

```bash
php artisan limen-ai:install          # config, env template, views, stubs
php artisan migrate
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

Copy environment keys from `.env.limen-ai.example` (published) or [`.env.example`](.env.example):

```env
LIMEN_AI_DEFAULT_AGENT=example
LIMEN_AI_PROVIDER=fake

# Production:
# LIMEN_AI_PROVIDER=openai
# OPENAI_API_KEY=sk-...

# Attachments, queue, broadcasting — see .env.example for all keys
```

Provider setup: [docs/providers.md](docs/providers.md)

---

## Quick start (5 minutes)

### Step 1 — Create a tool in your host app

```php
// app/LimenAi/Tools/ExampleEchoTool.php
namespace App\LimenAi\Tools;

use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Tools\ConfigToolDefinition;

class ExampleEchoTool implements Tool
{
    public function key(): string { return 'example_echo'; }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig('example_echo', config('limen-ai.tools.example_echo'));
    }

    public function handle(array $input, ToolExecutionContext $context): array
    {
        return ['message' => $input['message'], 'user_id' => $context->userId()];
    }
}
```

Generate stubs automatically:

```bash
php artisan limen-ai:make:tool ExampleEcho
php artisan vendor:publish --tag=limen-ai-stubs
```

### Step 2 — Register the tool class in config

```php
// config/limen-ai.php
'tools' => [
    'example_echo' => [
        'class' => App\LimenAi\Tools\ExampleEchoTool::class,
        // ... remaining fields from published config
    ],
],
```

### Step 3 — Embed the chat UI

```blade
{{-- Full-page chat --}}
<x-limen-ai::chatbot agent="example" />

{{-- Floating widget --}}
<x-limen-ai::widget agent="example" />

{{-- Arabic RTL preset with auto light/dark --}}
<x-limen-ai::chatbot :theme="['preset' => 'arabic', 'mode' => 'auto']" />
```

### Step 4 — Test without calling a real LLM

```bash
php artisan limen-ai:agent:test example
php artisan limen-ai:run example
composer test
```

---

## How it works

```
User message
    ↓
ConversationService
    ↓
AgentRuntime::run()
    ↓
ContextBuilder (auth context, memory, knowledge, history)
    ↓
LlmProvider::chat()
    ↓
Tool call requested?
 ┌──┴──┐
No    Yes → ToolPipeline
              ├─ Authorization (Gates)
              ├─ Input validation
              ├─ Approval gate (if confirmation required)
              ├─ Idempotency check
              ├─ Execute host tool / HTTP integration
              └─ Audit + events
    ↓
Final response → persist → broadcast (optional)
```

**Package owns:** orchestration, runtime, UI components, provider adapters, security helpers.

**Your app owns:** Eloquent models, business rules, policies, and tool implementations under `App\LimenAi\`.

Full architecture: [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md)

---

## Built-in agents

| Key | Purpose |
|-----|---------|
| `example` | Development agent with `example_echo` tool and fake LLM provider |
| `limen_3pl` | Logistics demo — shipment lookup + approval-gated customer messaging |

Switch models per agent — no code changes:

```php
'support_claude' => ['provider' => 'anthropic', 'model' => 'claude-sonnet-4-20250514'],
'support_gpt'    => ['provider' => 'openai',    'model' => 'gpt-4.1-mini'],
'support_or'     => ['provider' => 'openrouter','model' => 'anthropic/claude-3.5-sonnet'],
```

---

## Programmatic usage

Run agents from controllers, jobs, or event listeners:

```php
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Runtime\RunContextData;

$runId = app(AgentRuntime::class)->run(
    agent: 'example',
    conversationId: 'conv-' . $user->id,
    message: 'Summarize open tickets for today.',
    context: RunContextData::make(['user_id' => $user->id]),
);
```

Always pass `user_id` from Laravel auth — never from LLM or client input.

Or use the `LimenAi` facade (aliased in `composer.json`):

```php
use LimenAi\Facades\LimenAi;

$runId = LimenAi::run('example', $conversationId, 'Hello', ['user_id' => $user->id]);
```

---

## HTTP API

Default prefix: `/limen-ai` (configurable via `LIMEN_AI_ROUTE_PREFIX`).

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/conversations` | Start a conversation `{ "agent": "example" }` |
| GET | `/conversations/{id}` | Fetch conversation with messages |
| POST | `/conversations/{id}/messages` | Send a user message `{ "message": "...", "attachment_ids": [] }` |
| GET | `/conversations/{id}/attachments` | List uploaded attachments |
| POST | `/conversations/{id}/attachments` | Upload a file (`multipart/form-data`, field `file`) |
| DELETE | `/attachments/{id}` | Delete an attachment |
| GET | `/runs/{id}` | Poll run status and output |
| GET | `/runs/{id}/observability` | Audit trace and token usage |
| POST | `/approvals/{id}/approve` | Approve a paused tool action |
| POST | `/approvals/{id}/reject` | Reject a paused tool action |

---

## Use cases

1. **Customer support chatbot** — authenticated users query orders via host-app tools; Gates enforce `orders.read`.
2. **Internal ops assistant** — warehouse staff look up shipments; outbound messages require human approval.
3. **Multi-model routing** — cheap models for triage, premium models for complex reasoning — per agent in config.
4. **Approval-gated actions** — set `confirmation: true` on tools that send email, charge cards, or delete data.
5. **Workflow playbooks** — agent drafts → approval step → tool executes (e.g. delay notifications).
6. **Policy-aware RAG** — inject handbook documents; content is sanitized before reaching the LLM.
7. **Document-aware chat** — upload `.txt`, `.md`, `.csv`, or `.json` files; extracted text is injected into the agent context (optional vector RAG when `knowledge.driver=vector`).
8. **Custom SPA frontend** — call the HTTP API from React/Vue; subscribe to Pusher for live updates.
9. **CI-safe tests** — queue responses on `FakeLlmProvider`; assert tool calls and authorization with zero API cost.

Detailed examples: [docs/index.html#use-cases](docs/index.html#use-cases)

---

## Configuration reference

Primary file: `config/limen-ai.php`

| Key | Purpose |
|-----|---------|
| `default_agent` | Fallback agent key |
| `providers` | LLM provider registry (OpenAI, Anthropic, Gemini, OpenRouter, fake) |
| `embeddings` | Embedding providers for vector RAG |
| `agents` | Agent definitions (instructions, tools, persona, limits) |
| `tools` | Tool definitions (class-based or HTTP integration) |
| `skills` | Reusable instruction + tool bundles |
| `workflows` | Multi-step workflow definitions |
| `knowledge` | RAG collections and vector store driver |
| `memory` | Memory store, retriever, and strict policy |
| `attachments` | Upload limits, storage driver, text extraction, optional vector RAG |
| `security` | SSRF rules, injection patterns, redaction keys |
| `observability` | Audit, usage tracking, trace toggles |
| `ui` | Chat widget, themes, route prefix, middleware |
| `queue` | Background agent run dispatch |
| `broadcasting` | Realtime updates (Pusher-compatible) |

Full schema: [docs/configuration-schema.md](docs/configuration-schema.md)

---

## Artisan commands

| Command | Description |
|---------|-------------|
| `limen-ai:install` | Publish config, env example, views, assets, and stubs |
| `limen-ai:doctor` | Health check: config, providers, queue, broadcasting |
| `limen-ai:validate` | Validate all agent/tool/skill/workflow definitions |
| `limen-ai:list` | Summary of registered components |
| `limen-ai:agents` / `tools` / `skills` / `workflows` | List one component type |
| `limen-ai:logs` | Show buffered audit log entries |
| `limen-ai:make:agent` | Generate agent stub |
| `limen-ai:make:tool` | Generate tool class + optional test |
| `limen-ai:make:skill` | Generate skill definition |
| `limen-ai:make:workflow` | Generate workflow definition |
| `limen-ai:make:connector` | Generate HTTP connector config |
| `limen-ai:make:provider` | Generate custom LLM provider adapter |
| `limen-ai:make:memory` | Generate custom memory store |
| `limen-ai:make:knowledge` | Generate custom knowledge retriever |
| `limen-ai:agent:test {agent}` | Run agent once against fake or live provider |
| `limen-ai:tool:test {tool}` | Execute a tool with JSON input |
| `limen-ai:workflow:test {workflow}` | Dry-run a workflow |
| `limen-ai:run {agent}` | Interactive CLI chat session |

Full command map: [docs/artisan-command-map.md](docs/artisan-command-map.md)

---

## Testing

```bash
composer install
composer test              # full PHPUnit suite
composer test:gates        # architecture + security merge gates
composer test:release      # pre-release validation (tests + gates)
```

Use `FakeLlmProvider` in tests — never call real LLM APIs in CI:

```php
$fake = app(FakeLlmProvider::class);
$fake->queueResponse(LlmResponseData::fromArray([
    'content' => 'Done.',
    'tool_calls' => [[
        'id' => 'call_1',
        'type' => 'function',
        'function' => ['name' => 'example_echo', 'arguments' => '{"message":"hi"}'],
    ]],
]));
```

CI matrix: PHP 8.2 / 8.3 × Laravel 11 / 12. Details: [docs/ci.md](docs/ci.md) · [docs/development/TESTING.md](docs/development/TESTING.md)

---

## Documentation index

| Document | Description |
|----------|-------------|
| **[docs/installation.md](docs/installation.md)** | **Install via Packagist, VCS, path repo, or private registry** |
| [docs/index.html](docs/index.html) | Interactive documentation hub |
| [docs/README.md](docs/README.md) | Full markdown documentation index |
| [docs/architecture/ARCHITECTURE.md](docs/architecture/ARCHITECTURE.md) | System architecture and module map |
| [docs/project/AI_SPEC.md](docs/project/AI_SPEC.md) | Master specification |
| [SECURITY.md](SECURITY.md) | Threat model and security controls |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [docs/providers.md](docs/providers.md) | LLM and embedding providers |
| [docs/agent-configuration.md](docs/agent-configuration.md) | Persona, tone, language, memory, and quality |
| [docs/release.md](docs/release.md) | Publish and release workflow |
| [docs/limen-integration.md](docs/limen-integration.md) | Limen 3PL host integration demo |
| [docs/theming.md](docs/theming.md) | Chat UI themes and RTL support |
| [docs/observability.md](docs/observability.md) | Traces, audit logs, usage metrics |
| [docs/ci.md](docs/ci.md) | CI matrix and merge gates |
| [docs/branching.md](docs/branching.md) | Branch flow: feature → stg → main |

---

## First host application: Limen 3PL

The reference integration is **Limen**, a 3PL/fulfillment management system. The package stays domain-agnostic — Limen-specific tools live in the host app under `App\LimenAi\Tools\`.

Publish the demo stubs:

```bash
php artisan vendor:publish --tag=limen-ai-limen-demo
```

Guide: [docs/limen-integration.md](docs/limen-integration.md)

---

## Development

Work on the package itself (not a host app):

```bash
git clone https://github.com/hatem-isnaad/limen-ai.git
cd limen-ai
composer install
composer test:release
```

Link into a Laravel app for integration testing — see [docs/installation.md](docs/installation.md#method-3--path-repository-local-development).

**Branch flow:** feature branch → **`stg`** (CI runs full test matrix) → **`main`** (production release).

---

## Releases

Limen AI follows [Semantic Versioning](https://semver.org/):

| Change | Version bump |
|--------|--------------|
| Breaking API or config schema | MAJOR |
| New backward-compatible features | MINOR |
| Bug fixes and security patches | PATCH |

Release guide: [docs/release.md](docs/release.md)

---

## License

MIT — see [LICENSE](LICENSE).
