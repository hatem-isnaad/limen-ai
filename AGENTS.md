# Limen AI — AI Agent & Contributor Reference

> **Audience:** AI coding agents (Cursor, Copilot, Claude Code, etc.) and human contributors.  
> **Goal:** Understand what this package is, how it is built, how to use it, and how to contribute safely.

---

## 1. Package identity

| Field | Value |
|-------|-------|
| Name | Limen AI |
| Composer | `limen-ai/limen-ai` |
| Namespace | `LimenAi\` |
| Type | Laravel package (library) — **not** a standalone app |
| PHP | ^8.2 |
| Laravel | ^11.0 \| ^12.0 \| ^13.0 |
| Status | v1.0.5+ — all 22 roadmap phases complete |

**What it is:** A reusable Laravel AI agent framework with agents, runtime, tools, skills, workflows, memory, knowledge (RAG), attachments, approvals, chat UI, HTTP API, and observability.

**What it is not:** A SaaS product, a host app, or a place for business/domain logic. The first reference host is **Limen 3PL** — domain tools live in the host app under `App\LimenAi\`.

---

## 2. Golden rule (non-negotiable)

> **The LLM proposes actions. Laravel decides whether they are allowed and executes them safely.**

| LLM may | Laravel must |
|---------|--------------|
| Understand user intent | Authenticate users |
| Suggest tools + JSON arguments | Authorize via Gates/policies |
| Generate natural language | Validate all input |
| Reason over allowed context | Execute tools in PHP |
| | Scope data to the authenticated user |
| | Audit and log side effects |

**Never** trust `user_id`, tenant IDs, or permissions from LLM output or tool arguments. Always use `auth()` / `RunContextData` from the host request.

---

## 3. Repository layout

```
limen-ai/
├── AGENTS.md                 ← YOU ARE HERE (AI contributor entry point)
├── README.md                 ← User-facing overview + quick install
├── CHANGELOG.md              ← Version history
├── SECURITY.md               ← Threat model
├── composer.json             ← Package manifest
├── config/limen-ai.php       ← Default configuration (published to host)
├── database/migrations/      ← Optional persistence tables
├── docs/                     ← All human documentation
│   ├── installation.md       ← Install methods (Packagist, VCS, path repo)
│   ├── architecture/         ← ARCHITECTURE, RULES, DECISIONS
│   ├── project/              ← AI_SPEC, STATUS, ROADMAP, IMPLEMENTATION
│   └── development/          ← TESTING, ci.md
├── routes/limen-ai.php       ← HTTP API routes
├── src/                      ← Package source (LimenAi\)
├── resources/                ← Blade views, CSS, JS (chat UI)
├── stubs/                    ← Generator stubs + limen-ai.env.example
├── tests/                    ← Full test suite (415+ tests)
├── examples/limen-host/      ← Reference host integration
└── .ai/                      ← AI workflow rules, checklists, REFERENCE.md
```

---

## 4. Source module map (`src/`)

| Module | Path | Responsibility |
|--------|------|----------------|
| **Agents** | `src/Agents/` | Config-backed agent defs, resolver, instruction/persona composition |
| **Runtime** | `src/Runtime/` | `DefaultAgentRuntime` — multi-step LLM ↔ tool loop, checkpoints, limits |
| **Tools** | `src/Tools/` | `ToolPipeline` — auth, validation, idempotency, audit, execution |
| **Skills** | `src/Skills/` | Reusable instruction + tool bundles |
| **Workflows** | `src/Workflows/` | Multi-step graphs (agent, tool, approval, branch steps) |
| **Providers** | `src/Providers/` | LLM + embedding adapters (OpenAI, Anthropic, Gemini, Fake, …) |
| **Memory** | `src/Memory/` | Scoped memory store + retrieval into runtime context |
| **Knowledge** | `src/Knowledge/` | Config or vector RAG retrieval |
| **Attachments** | `src/Attachments/` | Upload, validate, extract text, inject into runtime |
| **Conversations** | `src/Conversations/` | Threads, messages, history for runtime |
| **Authorization** | `src/Authorization/` | Gates integration, approvals, guest sessions |
| **Integrations** | `src/Integrations/` | Declarative HTTP tools + SSRF validation |
| **Security** | `src/Security/` | Prompt injection sanitization, redaction, SSRF |
| **Observability** | `src/Observability/` | Audit buffer, usage tracking, trace IDs |
| **Broadcasting** | `src/Broadcasting/` | Pusher + null realtime adapters |
| **Http** | `src/Http/` | REST API controllers (UI-agnostic) |
| **Jobs** | `src/Jobs/` | Queued agent run dispatch |
| **Ui** | `src/Ui/` | Theme resolution for chat components |
| **Console** | `src/Console/` | All `limen-ai:*` Artisan commands |
| **Contracts** | `src/Contracts/` | **Interfaces only** — extension points |
| **Events** | `src/Events/` | Domain events (agent, tool, conversation, approval) |
| **Facades** | `src/Facades/LimenAi.php` | Facade → `LimenAiManager` |
| **Support** | `src/Support/LimenAiManager.php` | Programmatic `run()` / `startWorkflow()` |

**Entry point for Laravel:** `src/LimenAiServiceProvider.php` — binds all contracts, registers commands, routes, events.

---

## 5. How components are defined (config-driven)

Everything is registered via **`config/limen-ai.php`** (published to the host app). No auto-discovery of PHP classes.

| Component | Config key | Repository | Definition class |
|-----------|------------|------------|------------------|
| Agent | `agents.{key}` | `ConfigAgentRepository` | `ConfigAgentDefinition` |
| Tool | `tools.{key}` | `ConfigToolRepository` | `ConfigToolDefinition` |
| Skill | `skills.{key}` | `ConfigSkillRepository` | `ConfigSkillDefinition` |
| Workflow | `workflows.{key}` | `ConfigWorkflowRepository` | `ConfigWorkflowDefinition` |
| Knowledge | `knowledge.collections.{key}` | `ConfigKnowledgeRepository` | — |
| HTTP connector | `integrations.connectors.{key}` | `ConfigHttpConnectorRepository` | `ConfigHttpConnector` |

### Example agent (minimal)

```php
'agents' => [
    'example' => [
        'provider' => 'fake',
        'model' => 'fake',
        'instructions' => 'You are a helpful assistant.',
        'tools' => ['example_echo'],
    ],
],
```

### Example tool (class-based — host app)

```php
// config/limen-ai.php
'tools' => [
    'my_tool' => [
        'class' => App\LimenAi\Tools\MyTool::class,
        'description' => 'Does something useful',
        'parameters' => [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'string']],
            'required' => ['id'],
        ],
    ],
],
```

```php
// app/LimenAi/Tools/MyTool.php
namespace App\LimenAi\Tools;

use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Tools\BaseTool;
use LimenAi\Tools\ConfigToolDefinition;

class MyTool extends BaseTool
{
    public function key(): string { return 'my_tool'; }

    public function definition(): \LimenAi\Contracts\Tools\ToolDefinition
    {
        return ConfigToolDefinition::fromConfig($this->key(), config('limen-ai.tools.my_tool'));
    }

    public function authorize(array $input, ToolExecutionContext $context): bool
    {
        return $context->userId() !== null; // false = tool blocked
    }

    public function handle(array $input, ToolExecutionContext $context): array
    {
        return ['ok' => true];
    }
}
```

Generate stubs: `php artisan limen-ai:make:tool MyTool`

---

## 6. Runtime execution flow

**Class:** `src/Runtime/DefaultAgentRuntime.php`

```
User message
    → authorize agent + validate RunContextData
    → sanitize message (ContentSanitizer)
    → load conversation history
    → inject memory + knowledge + attachments + persona
    → create Run record
    → LOOP (under RuntimeLimits):
        → LlmProvider::chat(messages, tool schemas)
        → if no tool_calls → AgentResponseGuard → return final text
        → foreach tool_call:
            → ToolPipeline::execute()
                → authorize → validate → approval gate → idempotency → execute → audit
            → append tool result to messages
    → persist run + broadcast events
```

**Pause for approval:** Tools with `confirmation: true` throw `ApprovalRequiredException` → run status `WAITING_APPROVAL` → resume via API or `ResumeAgentRunJob`.

**Programmatic usage:**

```php
use LimenAi\Facades\LimenAi;
use LimenAi\Runtime\RunContextData;

$runId = LimenAi::run('example', $conversationId, 'Hello', [
    'user_id' => auth()->id(),  // REQUIRED — from Laravel auth
]);
```

---

## 7. Tool pipeline (security-critical)

**Class:** `src/Tools/ToolPipeline.php`

Order is fixed — do not skip steps when extending:

1. Resolve tool definition from repository
2. `AuthorizationService::authorizeTool()` — Gates/policies
3. `ToolInputValidator::validate()` — JSON schema
4. Confirmation gate → pause if `confirmation: true`
5. Idempotency check (cache driver)
6. Audit `tool.started` + dispatch `ToolStarted`
7. Execute via `ClassBasedToolExecutor` or `DeclarativeHttpToolExecutor`
8. Audit `tool.completed` + dispatch `ToolCompleted`

HTTP tools pass through `HttpIntegrationValidator` (SSRF protection).

---

## 8. HTTP API

**Routes:** `routes/limen-ai.php` · Prefix: `config('limen-ai.ui.route_prefix')` (default `limen-ai`)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/conversations` | Start conversation `{ "agent": "example" }` |
| GET | `/conversations/{id}` | Get conversation + messages |
| POST | `/conversations/{id}/messages` | Send message `{ "message": "...", "attachment_ids": [] }` |
| GET/POST | `/conversations/{id}/attachments` | List / upload files |
| DELETE | `/attachments/{id}` | Delete attachment |
| GET | `/runs/{id}` | Poll run status |
| GET | `/runs/{id}/observability` | Audit trace + token usage |
| POST | `/approvals/{id}/approve` | Approve paused tool |
| POST | `/approvals/{id}/reject` | Reject paused tool |

**Blade UI:** `<x-limen-ai::chatbot agent="example" />` — consumes same API + optional Pusher.

---

## 9. Artisan commands

| Category | Commands |
|----------|----------|
| Install | `limen-ai:install`, `limen-ai:doctor`, `limen-ai:validate`, `limen-ai:import:knowledge` |
| Inspect | `limen-ai:list`, `limen-ai:agents`, `:tools`, `:skills`, `:workflows`, `:logs` |
| Test | `limen-ai:agent:test`, `:tool:test`, `:workflow:test`, `limen-ai:run` |
| Generate | `limen-ai:make:agent`, `:tool`, `:skill`, `:workflow`, `:connector`, `:provider`, `:memory`, `:knowledge` |

Full map: `docs/artisan-command-map.md`

---

## 10. How host apps install this package

See `docs/installation.md`. Summary:

| Method | When |
|--------|------|
| `composer require limen-ai/limen-ai` | Packagist / production |
| VCS repository in `composer.json` | Git install without Packagist |
| Path repository `"../limen-ai-main"` | Local development (symlink) |
| Private Composer registry | Enterprise / air-gapped |

After install:

```bash
php artisan limen-ai:install
php artisan migrate
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

Env template: publish tag `limen-ai-env` → `.env.limen-ai.example` or see `.env.example` in package root.

---

## 11. Extension points (host app)

| Extension | How |
|-----------|-----|
| Custom tools | `App\LimenAi\Tools\*` implementing `Contracts\Tools\Tool` |
| HTTP tools | `tools.*.integration` + `integrations.connectors` |
| Custom LLM provider | `providers.drivers.{name}` + implement `Contracts\Providers\LlmProvider` |
| Swap persistence | `runtime.run_repository`, `conversations.repository`, `memory.store`, `attachments.store` |
| Swap repositories | `repositories.agent`, `.tool`, etc. |
| Events | Subscribe to `LimenAi\Events\*` |
| Themes | `config('limen-ai.ui.theme')` — see `docs/theming.md` |
| Disable UI | `LIMEN_AI_UI_ENABLED=false` — API-only mode |

**Host app owns:** Eloquent models, policies, Gates, business services, domain tools.  
**Package owns:** Runtime loop, tool pipeline, provider adapters, generic UI, security primitives.

---

## 12. Testing (required for all changes)

```bash
composer test              # all PHPUnit suites
composer test:gates        # architecture + security gates
composer test:release      # full pre-release validation
```

### Test layout

| Suite | Path | Purpose |
|-------|------|---------|
| Unit | `tests/Unit/{Module}/` | Mirrors `src/` modules |
| Feature | `tests/Feature/` | API, commands, end-to-end flows |
| Integration | `tests/Integration/` | Binding / wiring tests |
| Architecture | `tests/Architecture/` | **Boundary enforcement — must pass** |
| Security | `tests/Security/` | Critical security matrix |

### Rules

- **Never** call real LLM APIs in tests — use `FakeLlmProvider`
- Queue responses: `app(FakeLlmProvider::class)->queueResponse(...)`
- Database tests extend `DatabaseTestCase`
- New security-critical code → add paths to `CriticalCoverageGateTest`

---

## 13. Architecture boundaries (enforced by tests)

**Do not violate these** — `tests/Architecture/ModuleBoundaryTest.php` and `PackageBoundaryTest.php` will fail.

| Rule | Detail |
|------|--------|
| No `App\` imports | Package `src/` must never import host app code |
| Contracts are pure | `src/Contracts/` — interfaces only, no concrete imports |
| Runtime isolation | `Runtime/` must not import `Http/`, `Ui/`, Blade, Pusher |
| Jobs abstraction | Jobs type-hint `AgentRuntime`, not `DefaultAgentRuntime` |
| Http isolation | Controllers must not import runtime internals |
| Pusher scope | `Pusher\` only allowed in `src/Broadcasting/` |
| Auth source | `user_id` from Laravel auth only — never from LLM/tool input |
| Business logic | Domain tools in host app, not package core |

Full rules: `docs/architecture/ARCHITECTURE_RULES.md`

---

## 14. How to contribute (AI agent workflow)

### Before coding

1. Read this file (`AGENTS.md`)
2. Read `docs/project/AI_SPEC.md` for product scope
3. Read `docs/architecture/ARCHITECTURE.md` for system design
4. Check `docs/architecture/DECISIONS.md` for accepted patterns
5. Check `docs/project/IMPLEMENTATION.md` for known deferrals

### While coding

- **Minimize scope** — smallest correct diff
- **Match conventions** — read surrounding code before adding
- **Use contracts** — bind implementations in `LimenAiServiceProvider`
- **Config-driven** — new agents/tools/skills go in config schema + repository
- **Test real behavior** — not trivial assertions
- **No fake completion** — don't mark unfinished work as done

### After coding

1. Run `composer test:release`
2. Update `CHANGELOG.md` (Unreleased section)
3. Update relevant docs in `docs/`
4. If architectural decision → `docs/architecture/DECISIONS.md`
5. If deferring scope → `docs/project/IMPLEMENTATION.md`

### Definition of done

```
Code + Tests + Docs + Config + Error handling + Security + Events + Architecture gates pass
```

---

## 15. Common contributor tasks

### Add a new tool (package example)

1. Add config entry in `config/limen-ai.php` under `tools`
2. Create class extending `LimenAi\Tools\BaseTool` (host: `App\LimenAi\Tools\*`) with `authorize()` + `handle()`
3. In simple auth mode (default), no Laravel Gates — return `false` from `authorize()` to block execution
4. Add unit test for tool behavior
5. Add feature test if it affects runtime/API
6. Run `php artisan limen-ai:validate`

Host black-box guide: `docs/black-box-host-guide.md`

### Add a new LLM provider

1. Implement `Contracts\Providers\LlmProvider`
2. Register in `config/limen-ai.php` → `providers.drivers`
3. Bind in `LimenAiServiceProvider` if needed
4. Add unit tests with mocked HTTP
5. Document in `docs/providers.md`

### Add a new HTTP API endpoint

1. Add route in `routes/limen-ai.php`
2. Create controller in `src/Http/Controllers/Api/`
3. Use `ConversationAccessGuard` for auth
4. Add feature test in `tests/Feature/`
5. Update README HTTP API table + `docs/index.html`

### Add a new Artisan command

1. Create command in `src/Console/`
2. Register in `LimenAiServiceProvider::registerConsole()`
3. Add feature test
4. Update `docs/artisan-command-map.md`

### Add a new config option

1. Add to `config/limen-ai.php` with `env()` helper
2. Add to `.env.example` and `stubs/limen-ai.env.example`
3. Use in service provider binding or relevant class
4. Document in `docs/configuration-schema.md`

---

## 16. Configuration quick reference

Key `config/limen-ai.php` sections:

| Key | Purpose |
|-----|---------|
| `default_agent` | Fallback agent key |
| `providers` | LLM drivers (fake, openai, anthropic, gemini, openrouter) |
| `embeddings` | Embedding providers for vector RAG |
| `agents` | Agent definitions |
| `tools` | Tool definitions |
| `skills` | Skill bundles |
| `workflows` | Workflow graphs |
| `knowledge` | RAG driver + collections |
| `memory` | Memory store + strict policy |
| `attachments` | Upload limits, store, RAG |
| `runtime` | Run/checkpoint/approval repository classes |
| `security` | SSRF, injection patterns, redaction |
| `queue` | Background agent runs |
| `broadcasting` | Realtime updates |
| `observability` | Audit, usage, traces |
| `ui` | Chat widget, themes, route prefix |
| `repositories` | Swap config repos for custom implementations |
| `paths` | Generator output paths in host app |

Full schema: `docs/configuration-schema.md`

---

## 17. Key documentation index

| Document | Path |
|----------|------|
| **This guide** | `AGENTS.md` |
| Install (all methods) | `docs/installation.md` |
| **Black-box host guide** | `docs/black-box-host-guide.md` |
| **Knowledge base setup** | `docs/knowledge-base-setup.md` |
| Host quickstart | `docs/host-quickstart.md` |
| Scaling agents & tools | `docs/scaling-agents-and-tools.md` |
| Host integration audit | `docs/HOST-INTEGRATION-AUDIT.md` |
| Interactive docs | `docs/index.html` |
| Doc index | `docs/README.md` |
| Product spec | `docs/project/AI_SPEC.md` |
| Architecture | `docs/architecture/ARCHITECTURE.md` |
| Architecture rules | `docs/architecture/ARCHITECTURE_RULES.md` |
| Decisions log | `docs/architecture/DECISIONS.md` |
| Implementation status | `docs/project/STATUS.md` |
| Deferred items | `docs/project/IMPLEMENTATION.md` |
| Security model | `SECURITY.md` |
| Testing strategy | `docs/development/TESTING.md` |
| CI matrix | `docs/ci.md` |
| Providers | `docs/providers.md` |
| Agent config | `docs/agent-configuration.md` |
| Limen 3PL demo | `docs/limen-integration.md` |
| Events | `docs/event-map.md` |
| Database schema | `docs/database-proposal.md` |

---

## 18. Anti-patterns (never do these)

- Import `App\Models\*` or any `App\` code from package `src/`
- Put business/domain logic in the package (shipment lookup, billing, etc.)
- Trust `user_id` from LLM tool arguments
- Call LLM APIs in PHPUnit tests without fakes
- Skip authorization in the tool pipeline
- Couple `DefaultAgentRuntime` to Blade or Pusher
- Mark roadmap phases incomplete as "done" without tests
- Add features without updating docs and CHANGELOG
- Break architecture boundary tests to "make it work"
- Put 20+ tools on a single agent (split agents; see `docs/scaling-agents-and-tools.md`)

---

## 19. AI-specific files in `.ai/`

| File | Purpose |
|------|------|
| `.ai/MASTER_PROMPT.md` | Short boot prompt for AI sessions |
| `.ai/REFERENCE.md` | Extended technical reference (module details) |
| `.ai/rules/core-rules.md` | Core implementation rules |
| `.ai/rules/security-rules.md` | Security-focused rules |
| `.ai/workflows/phase-workflow.md` | Phase-based delivery workflow |
| `.ai/checklists/phase-completion.md` | Done checklist |
| `.ai/templates/feature-spec-template.md` | Template for new features |

---

## 20. Quick verification commands

```bash
# Package development (in this repo)
composer install
composer test:release
php artisan limen-ai:doctor      # via Testbench in tests
php artisan limen-ai:validate

# In a host Laravel app (after path/VCS install)
php artisan limen-ai:install
php artisan limen-ai:doctor
php artisan limen-ai:list
php artisan limen-ai:agent:test example
```

---

*Last updated: 2026-09-23 · Package v1.0.5+ · Black-box guide: `docs/black-box-host-guide.md`*
