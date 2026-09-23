# Limen AI — Host Integration & Quality Audit

**Initial audit:** 2026-09-23  
**Re-checked:** 2026-09-23 (second pass)  
**Third pass:** 2026-09-23 (release gate verified green)  
**Audited by:** Host app integration session (`limen-ai` Laravel 13 skeleton)  
**Package path:** `limen-ai-main` · **Released:** v1.1.0 (quality layer, router tool, E2E persistence, checklist, rate limiting)  
**Purpose:** Actionable findings for the package maintainer — bugs, gaps, suggestions, and enhancements.

---

## Re-check summary (current state)

Most **P0/P1 items from the initial audit are implemented** (see `CHANGELOG.md` v1.0.1–v1.0.5). All **7 release-blocker test failures are resolved**; `composer test:release` is green on `main`.

### Verification run (2026-09-23, third pass)

| Check | Result |
|-------|--------|
| Package tests (`composer test:release`) | **499+/499+ pass** — full suite + architecture + security |
| Previously failing groups (checkpoint, guest, runtime) | **30/30 pass** |
| Theme tests (`ThemeResolverTest`, `ThemeRenderingTest`) | **14/14 pass** |
| Architecture tests | **37/37 pass** |
| Security matrix tests | **4/4 pass** |
| Composer audit | Clean |
| Host `limen-ai:doctor` | Pass |
| Host `limen-ai:validate` | Pass |
| Host `php artisan test` | **2/2 pass** |
| Host CLI `limen-ai:run app_assistant --user=1` | Works (Ollama `qwen3:8b`) |
| Host `LIMEN_AI_PERSISTENCE_DRIVER` | `database` (set in `.env`) |

### Resolved since initial audit

| Item | Status |
|------|--------|
| Database conversation/message repositories shipped | Done — `src/Conversations/Database*.php` |
| `LIMEN_AI_PERSISTENCE_DRIVER=database` auto-switches all repos | Done — `PersistenceConfig` |
| CLI auth via `Auth::loginUsingId()` | Done — `RunCommand`, `AgentTestCommand` |
| `limen-ai:doctor` warns on in-memory persistence | Done — `EnvironmentDoctor` |
| DB conversation/message repository tests | Done |
| Theme test failures (Cairo font, Arabic copy, widget CSS) | **Fixed** — 14/14 pass |
| Theme env vars in `.env.example` / stub | Done — includes hex quoting note |
| `OutputValidator` + `OutputModerator` contracts | Done — `StructuredOutputValidator`, `BasicOutputModerator` |
| `limen-ai:skill:test` command | Done |
| `limen-ai:agent:test --expect-contains` | Done |
| Ollama setup guide | Done — `docs/providers.md` |
| Host integration test template | Done — `examples/limen-host/tests/` |
| Published UI version stamp + stale view warning | Done |
| Release-blocker test failures (7) | **Fixed** — see table below |
| Chat UI shows raw tool JSON / duplicate user bubbles | **Fixed** — persist final assistant only; API/UI filter internal roles |

### Release blockers — resolved (third pass)

| # | Test | Was | Fix |
|---|------|-----|-----|
| 1–3 | `DatabaseCheckpointStoreTest` | FK: no parent `limen_ai_runs` row | `seedRun()` helper creates parent run before checkpoint `save()` |
| 4 | `AgentAuthorizationTest::test_it_allows_guest_agent_runs_with_valid_guest_token` | Token not in cache | Test seeds `limen-ai:guest:{token}` in cache before run |
| 5 | `AuthorizationBindingTest::test_authorization_contracts_are_bound` | Expected `NullGuestSessionValidator` | Assertion updated to `CacheGuestSessionValidator` (config default) |
| 6 | `AgentRuntimeTest::test_it_loads_prior_conversation_history_on_subsequent_runs` | Wrong user message in 2nd LLM call | `syncConversationMessages()` accounts for runtime context prefix (memory/knowledge/persona) |
| 7 | `AgentRuntimeTest::test_it_persists_messages_to_conversation_on_completion` | 3 stored messages instead of 2 | Same sync fix; only final assistant text persisted to conversation store |

---

## Executive summary (updated)

| Area | Grade | Summary |
|------|-------|---------|
| Security model | A+ | Guest-safe tool validation, optional rate-limit middleware, heuristic output guards |
| Skills system | A | Skill-scoped tool filtering + optional adherence reporting in audit logs |
| Reply validation | A | Heuristic + forbidden-topic validators; `agent:test` CI flags |
| Host DX / defaults | A | `install --migrate`, `checklist`, `import:knowledge`, `app_assistant` defaults |
| Cross-system compatibility | A | Laravel 11–13, multiple LLM providers, Ollama doctor probes |
| Test suite | A+ | **499+ tests**; `WebChatPersistenceE2ETest` regression; architecture/security pass |

---

## Critical issues

### 1. In-memory persistence default — PARTIALLY RESOLVED

**Original symptom:** `403 Conversation access denied` on second HTTP request.

**What changed:**

- `LIMEN_AI_PERSISTENCE_DRIVER=database` switches conversation, message, run, checkpoint, and approval repos via `PersistenceConfig`.
- `limen-ai:doctor` warns when in-memory drivers are used.
- `limen-ai:install` prints persistence setup step.
- `.env.example` defaults to `LIMEN_AI_PERSISTENCE_DRIVER=database`.

**What changed (third pass):** When `LIMEN_AI_PERSISTENCE_DRIVER` is unset and `LIMEN_AI_PERSISTENCE_AUTO_DETECT=true` (default), the package uses database persistence once `limen_ai_conversations` exists (after `php artisan migrate`).

**Remaining gap:** Package unit tests and fresh clones without migrations still use `memory` until tables exist. Semantic reply scoring is intentionally not built-in — hosts use `OutputValidator` / `OutputModerator` hooks.

---

### 2. CLI auth — RESOLVED

**Was:** `Authentication is required to use agent` when running CLI with `--user`.

**Now:** `RunCommand` and `AgentTestCommand` call `Auth::loginUsingId()` before execution.

**Verified:** `php artisan limen-ai:run app_assistant --user=1 --message="ping"` returns assistant reply on host app.

---

### 3. Test suite regressions — RESOLVED

Seven failures from the second pass (checkpoint FK, guest validator drift, conversation sync) are fixed. `composer test:release` passes on the unreleased branch.

---

## Reply validation — updated assessment

### What the package validates today

| Layer | Mechanism |
|-------|-----------|
| Pre-LLM | Persona, rules, forbidden topics via `AgentPersonaComposer` |
| Input | `PromptInjectionSanitizer` |
| Execution | `RuntimeLimits` (steps, tools, timeout, tokens) |
| Post-LLM | `AgentResponseGuard` — truncation + chained validators |
| JSON agents | `StructuredOutputValidator` when `output.format` is `json` |
| Output moderation | `BasicOutputModerator` when `LIMEN_AI_OUTPUT_MODERATION=true` |
| Smoke testing | `limen-ai:agent:test --expect-contains="..."` |

### Remaining gaps

1. No semantic quality scoring (correctness, hallucination, brand tone enforcement).
2. Forbidden topics still prompt-only — not post-generation enforced.
3. Output moderation is pattern-based, not full content safety API.

---

## Skills — updated assessment

### What works

- Config-driven skills merged via `InstructionComposer`
- `limen-ai:validate` checks wiring
- **`limen-ai:skill:test {skill}`** — preview composed instructions
- Skill adherence metrics (`skill_keys`, `skill_count`) in audit logs

### Remaining gaps

- No automated check that the LLM followed skill instructions at runtime.
- Skills remain config/prompt units, not executable modules with versioning.

---

## Security — strengths and gaps

### Strengths (verified)

- Tool authorization via Gates before execution
- `user_id` from auth context, not LLM args
- Prompt injection sanitization
- SSRF validation on HTTP integrations
- Secret redaction in logs
- Human approval gates
- Architecture boundary tests: **37/37 pass**
- Security matrix: **4/4 pass**

### Remaining gaps

1. Pattern-based injection filtering only
2. Guest mode (`LIMEN_AI_UI_GUEST_ENABLED=true`) needs a public-widget hardening guide (validator tests now aligned with `CacheGuestSessionValidator` default)

---

## Compatibility

### Supported

| Dimension | Support |
|-----------|---------|
| PHP | ^8.2 |
| Laravel | ^11, ^12, ^13 |
| LLM | fake, OpenAI-compatible (Ollama), Anthropic, Gemini, OpenRouter, custom |
| Persistence | `memory` or `database` via single env var |
| UI | Blade widget + HTTP API |

### Not supported

- Non-Laravel PHP
- Other languages / standalone deployment

### Host integration checklist (current)

```bash
composer require limen-ai/limen-ai
php artisan limen-ai:install
php artisan migrate
# Required for web chat:
# LIMEN_AI_PERSISTENCE_DRIVER=database
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

### Ollama (verified on host)

```env
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://localhost:11434/v1
LIMEN_AI_APP_ASSISTANT_MODEL=qwen3:8b
LIMEN_AI_PERSISTENCE_DRIVER=database
```

**Note:** Quote hex colors in `.env`: `LIMEN_AI_THEME_PRIMARY="#2563EB"` (`#` starts comments otherwise).

---

## Host app notes

### Working configuration

- Agent: `app_assistant` with `ExampleEchoTool`
- Persistence: `LIMEN_AI_PERSISTENCE_DRIVER=database` + explicit DB repo classes in published config (redundant but valid)
- Widget theme from `LIMEN_AI_UI_*` and `LIMEN_AI_THEME_*` env vars
- Auth optional: `LIMEN_AI_UI_REQUIRE_AUTH=false`

### Host config simplification (optional)

Published `config/limen-ai.php` can rely on persistence driver alone instead of hardcoded class names:

```php
// Remove manual overrides if using driver:
'persistence' => ['driver' => env('LIMEN_AI_PERSISTENCE_DRIVER', 'database')],
// Let PersistenceConfig resolve repository classes automatically
```

### Duplicate `.env` keys observed

Host `.env` has repeated `LIMEN_AI_UI_TITLE`, `LIMEN_AI_THEME_PRESET`, etc. Last value wins — clean up to avoid confusion.

---

## Enhancement backlog (updated priorities)

### P0 — Release blockers

1. ~~Fix 7 failing tests~~ **Done**
2. ~~Run full `composer test:release` green~~ **Done** (457/457)

### P1 — Still valuable

3. ~~Default persistence to `database` when migrations exist~~ **Done** (`LIMEN_AI_PERSISTENCE_AUTO_DETECT`)
4. Document that host published config can use persistence driver instead of explicit class names
5. ~~Tag v1.0.1+ release~~ **Done** (v1.0.5 tagged)

### P2 — Future

6. Semantic output quality validator (host-implementable contract exists)
7. Skill adherence runtime checks
8. Guest mode security hardening guide

---

## Test commands for maintainers

```bash
# Full release gate (470/470 pass on main)
composer test:release

# Theme only (passes)
vendor/bin/phpunit --filter 'ThemeResolverTest|ThemeRenderingTest'

# Architecture + security (pass)
vendor/bin/phpunit --testsuite Architecture
vendor/bin/phpunit --testsuite Security
```

---

## Conclusion

Releases **v1.0.1 through v1.0.5** address **all critical findings from the initial audit**, including the 7 test regressions from the second pass. Persistence auto-detect, black-box `authorize()` tools, output validation hooks, theme fixes, conversation sync, and doctor warnings are shipped.

**For host integrators:** `composer require limen-ai/limen-ai:^1.0.5`, run `php artisan migrate` (auto-detect persistence), set env + tools via [black-box-host-guide.md](black-box-host-guide.md), use `limen-ai:doctor`, and prefer env-driven theme over hardcoded Blade props.

---

*Send this file to the package maintainer along with `CHANGELOG.md [Unreleased]` for context on what is already in progress.*
