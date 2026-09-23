# Prompt: Bring Limen AI to 10/10

Copy everything below the line into the **limen-ai/limen-ai** package repo (Cursor, Claude, or GitHub issue) as the task brief.

---

## Mission

You are working on **Limen AI** (`limen-ai/limen-ai`) — a Laravel AI agent framework (agents, tools, skills, workflows, memory, knowledge, chat UI).

**Current state (verified by host integration audit, 2026-09-23):**

- `composer test:release` → **474/474 pass** (architecture 37/37, security 4/4)
- Releases **v1.0.1–v1.0.5** shipped: DB persistence, auto-detect, CLI auth, output hooks, Ollama doctor probe, theme fixes, conversation sync, scaling docs, black-box host guide
- **Overall rating: 8.5/10** — production-ready for Laravel, not yet “perfect”

**Your goal:** Implement the remaining work below so the package scores **10/10** on security, architecture, DX, production readiness, reply validation, and multi-tool scaling — without breaking existing host apps or test gates.

**Non-negotiables:**

1. Run `composer test:release` green before finishing
2. Run `vendor/bin/pint --dirty` on changed PHP files
3. Follow existing conventions in `AGENTS.md`, `.ai/REFERENCE.md`, and sibling files
4. Do **not** import host `App\` code into the package
5. Do **not** remove or weaken existing security boundaries
6. Update `CHANGELOG.md` under `[Unreleased]` for every user-facing change
7. Add tests for every new behavior (Pest/PHPUnit — match existing suite style)

---

## Already done (do NOT redo)

These were audit findings that are **fixed and tested**. Do not regress them:

| Area | Status |
|------|--------|
| `DatabaseConversationRepository` / `DatabaseMessageRepository` | Done + tests |
| `LIMEN_AI_PERSISTENCE_DRIVER` + auto-detect when `limen_ai_conversations` exists | Done + tests |
| CLI `Auth::loginUsingId()` in `limen-ai:run` and `limen-ai:agent:test` | Done + tests |
| `limen-ai:doctor` persistence warnings; fail when UI enabled + in-memory | Done + tests |
| Ollama endpoint + model probe in doctor | Done + tests (`EnvironmentDoctorOllamaTest`) |
| Checkpoint FK test seeding | Done |
| Guest validator aligned with `CacheGuestSessionValidator` | Done |
| `syncConversationMessages()` — persist final assistant text only | Done |
| API/UI filter internal `tool` role messages | Done |
| Theme env vars (`LIMEN_AI_UI_*`, `LIMEN_AI_THEME_*`) | Done + 14 theme tests |
| `OutputValidator` / `OutputModerator` contracts | Done |
| `StructuredOutputValidator`, `BasicOutputModerator`, `ChainedOutputValidator` | Done + tests |
| `limen-ai:skill:test`, `limen-ai:agent:test --expect-contains` | Done + tests |
| Skill metrics (`skill_keys`, `skill_count`) in audit logs | Done + tests |
| Published UI `VERSION` + stale view doctor warning | Done + tests |
| `docs/scaling-agents-and-tools.md` + agent tool-count validator warnings | Done |
| Guest mode guide in `SECURITY.md` | Done |
| `examples/limen-host/` integration template | Done |
| Placeholder API key detection in doctor | Done |

---

## Gap analysis → 10/10 roadmap

Current scores → target:

| Area | Now | Target | Gap |
|------|-----|--------|-----|
| Security | 9/10 | 10/10 | Built-in rate limits, stronger guest defaults, output safety |
| Architecture | 9/10 | 10/10 | Optional tool router, cleaner extension points |
| Test coverage | 9/10 | 10/10 | Full HTTP E2E persistence + widget smoke tests |
| Developer experience | 8.5/10 | 10/10 | Zero-config web chat after install |
| Production readiness | 8.5/10 | 10/10 | Install migrates + verifies; no foot-guns |
| Reply / AI quality | 7/10 | 10/10 | Optional built-in quality layer |
| Multi-tool scaling | 8/10 | 10/10 | Skill-based tool filtering, router agent pattern |

---

## P0 — Must ship (blocks 10/10)

### 1. Zero-config web chat after install

**Problem:** Fresh install without `migrate` still uses in-memory until tables exist. Integrators hit `403 Conversation access denied`.

**Implement:**

- `limen-ai:install` should print clear next steps AND optionally offer `--migrate` flag that runs Limen AI migrations
- After install, if UI enabled and tables missing → doctor **fails** with actionable message (not just warn)
- Document in `installation.md`: single command path to working widget

**Acceptance:**

- New Laravel app: `composer require` → `limen-ai:install` → `migrate` → widget works on 2nd HTTP message without manual config debugging
- Test: feature test simulating create conversation → send message across two requests with auto-detect

### 2. Full HTTP E2E persistence test

**Problem:** Unit/integration tests exist but no single feature test proving the original host bug never returns.

**Implement:**

- `tests/Feature/WebChatPersistenceE2ETest.php`:
  1. `POST /api/limen-ai/conversations` (or actual route)
  2. `POST` message to that conversation
  3. Assert 200, assistant reply persisted, second request sees history

**Acceptance:** Test passes with `persistence.driver = null`, `auto_detect = true`, migrations run.

### 3. `limen-ai:validate` must surface tool-count and persistence in output

**Problem:** Hosts run validate but miss scaling/persistence issues.

**Implement:**

- Include persistence mode (memory/database/auto-detected) in validate output
- Promote tool-count warnings from `AgentValidator` to validate command output (warn at 16+, fail at 26+ optional flag `--strict`)

**Acceptance:** Tests for validate output; documented in `artisan-command-map.md`.

---

## P1 — Reply validation 7/10 → 10/10

### 4. Optional built-in `HeuristicOutputValidator`

**Problem:** Semantic scoring is documented as “host only” — most hosts never implement it.

**Implement:**

- `LimenAi\Agents\HeuristicOutputValidator` implementing `OutputValidator`:
  - Reject empty/whitespace-only replies
  - Reject replies that echo system prompt markers or `[INST]`-style leaks
  - Reject JSON-looking garbage when `output.format = text`
  - Optional: reject replies over N sentences when `persona.response_style = concise`
- Register in `ChainedOutputValidator` when `quality.heuristic_validation = true` (default **true** in non-local env, **false** in local/testing)
- Config: `LIMEN_AI_HEURISTIC_VALIDATION=true`

**Acceptance:** Unit tests for each heuristic; no false positives on normal assistant replies in existing feature tests.

### 5. `limen-ai:agent:test --expect-not-contains` and `--min-length`

**Problem:** Smoke testing is too limited for CI quality gates.

**Implement:**

- `--expect-not-contains=*` (forbidden substrings)
- `--min-length=N`
- Exit code 1 on failure with diff snippet

**Acceptance:** Feature tests in `AgentTestCommandTest`.

### 6. Forbidden topics post-generation check (optional)

**Problem:** Forbidden topics are prompt-only today.

**Implement:**

- When agent has `persona.forbidden_topics`, run lightweight substring/pattern check on final assistant text before persist
- Config toggle: `quality.enforce_forbidden_topics` (default false — opt-in)
- Throw `OutputValidationException` with safe user message on violation

**Acceptance:** Unit test with forbidden topic “password” blocks reply containing “your password is”.

---

## P1 — Skills 8/10 → 10/10

### 7. Skill adherence runtime check (lightweight)

**Problem:** Only `skill_keys` logged — no signal if model ignored skill instructions.

**Implement:**

- After agent run, optional `SkillAdherenceReporter`:
  - If skill declares `required_phrases: []` or `must_not_contain: []`, evaluate final reply
  - Log warning to audit (not hard fail by default)
- Config: `quality.skill_adherence_check = true` (default false)

**Acceptance:** Test with skill that requires phrase “refund policy” — audit log contains adherence warning when missing.

### 8. Skill-scoped tool exposure

**Problem:** Agents with 20+ tools blow up LLM context.

**Implement:**

- Skill config optional key: `tools: ['tool_a', 'tool_b']`
- When agent runs, **intersect** agent tools with active skill tools if skill defines tools (document: empty = all agent tools)
- `InstructionComposer` documents which tools are active this turn

**Acceptance:**

- Agent with 10 tools, skill with 3 → LLM receives 3 schemas only
- Test in `DefaultAgentResolverTest` or new `SkillToolFilteringTest`
- Update `docs/scaling-agents-and-tools.md`

---

## P1 — Security 9/10 → 10/10

### 9. Built-in rate limiting middleware (optional publish)

**Problem:** Guest/public widgets need rate limits; docs say “wire middleware” but hosts skip it.

**Implement:**

- Publishable middleware `LimenAi\Http\Middleware\ThrottleAgentRequests`
- Config: `ui.rate_limit` → `max_attempts`, `decay_minutes` per IP + conversation
- Tag: `limen-ai-middleware`
- `limen-ai:install` mentions publishing it for public widgets

**Acceptance:** Feature test — 429 after limit exceeded on conversation create.

### 10. Safer guest defaults

**Problem:** `guest_allowed` easy to misconfigure on privileged agents.

**Implement:**

- `AgentValidator` **error** (not warning) when agent has `guest_allowed=true` AND any tool with `confirmation: true` or tools lacking `guest_safe: true` flag
- New optional tool config: `guest_safe: false` (default false)
- Document in `SECURITY.md`

**Acceptance:** Validate fails for dangerous guest agent config; passes when only read-only tools marked `guest_safe: true`.

---

## P2 — Architecture & scaling 8/10 → 10/10

### 11. Tool router pattern (optional meta-agent)

**Problem:** Hosts want one chat entry but many tools.

**Implement:**

- Document + stub: `router_agent` that only has one tool `delegate_to_agent(agent_key, message)`
- Or built-in `LimenAi\Tools\DelegateToAgentTool` reading allowed delegates from config
- Example in `examples/limen-host/config/multi-agent.example.php`

**Acceptance:** Example config validates; feature test delegates from router to specialist agent.

### 12. Dynamic tool registration (bounded)

**Problem:** Config-only tools don’t suit plugin architectures.

**Implement:**

- `ToolRepository` implementation `CompositeToolRepository` merging config + tagged container bindings
- Host registers: `LimenAi::registerTool('my_tool', MyTool::class)` in service provider
- Must still pass `limen-ai:validate`

**Acceptance:** Integration test registers runtime tool; agent invokes it; architecture test ensures no `App\` in package.

---

## P2 — Developer experience 8.5/10 → 10/10

### 13. `limen-ai:doctor --json` for CI

**Implement:** `--json` outputs `{ "ok": true, "failures": [], "warnings": [] }` for pipelines.

### 14. First-run checklist command

**Implement:** `limen-ai:checklist` prints ✅/❌ for: migrated, persistence, default agent valid, provider reachable, UI published version, tools authorized.

### 15. Align published host config stub

**Problem:** Published `config/limen-ai.php` drifts from package config.

**Implement:**

- Install publishes config matching package defaults (persistence block, no hardcoded DB class names)
- Test: `InstallCommandTest` asserts published config contains `persistence.auto_detect`

---

## P3 — Documentation (required for 10/10)

Update these files as you implement:

| File | Updates |
|------|---------|
| `CHANGELOG.md` | All changes under `[Unreleased]` |
| `docs/installation.md` | Zero-config path, rate limiting |
| `docs/agent-configuration.md` | Heuristic validator, forbidden topics, skill tool filtering |
| `docs/scaling-agents-and-tools.md` | Router agent, skill-scoped tools |
| `docs/artisan-command-map.md` | New flags and commands |
| `SECURITY.md` | Guest safe tools, rate limiting |
| `docs/HOST-INTEGRATION-AUDIT.md` | Mark new items resolved |
| `AGENTS.md` | New extension points |

Add **`docs/QUALITY-GATE.md`** explaining how to reach production quality:
- `composer test:release`
- `limen-ai:doctor --json`
- `limen-ai:validate --strict`
- `limen-ai:agent:test` in CI with `--expect-contains`

---

## Definition of done (10/10)

The package is **10/10** when ALL of the following are true:

1. **`composer test:release`** passes with **0 failures** (expect test count > 474)
2. **Host integration scenario** passes without manual config beyond `.env` LLM keys:
   - migrate → widget → 2 messages → history persists
3. **Security:**
   - Rate limit middleware available and tested
   - Guest misconfiguration caught by validate
   - Heuristic output validator enabled by default in production config
4. **Quality:**
   - `HeuristicOutputValidator` shipped and tested
   - Forbidden topics enforceable (opt-in)
   - Skill adherence reporting available (opt-in)
5. **Scaling:**
   - Skill-scoped tool filtering works
   - Router/delegate pattern documented + example works
6. **DX:**
   - `limen-ai:doctor --json` and `limen-ai:checklist` exist
   - Install/migrate path documented as ≤3 commands to working chat
7. **No regressions** on items listed in “Already done” section
8. **Composer audit** clean

---

## Suggested implementation order

```text
Week 1: P0 (E2E test, install/migrate DX, validate improvements)
Week 2: P1 quality (HeuristicOutputValidator, agent:test flags, forbidden topics)
Week 3: P1 security (rate limit middleware, guest_safe tools)
Week 4: P1 skills (skill tool filtering, adherence reporter)
Week 5: P2 (router tool, dynamic registration, doctor --json, checklist)
Week 6: Docs + final audit update + tag v1.1.0
```

---

## Reference files to read first

```text
AGENTS.md
.ai/REFERENCE.md
docs/HOST-INTEGRATION-AUDIT.md
docs/scaling-agents-and-tools.md
docs/black-box-host-guide.md
src/Support/PersistenceConfig.php
src/Support/EnvironmentDoctor.php
src/Runtime/DefaultAgentRuntime.php
src/Agents/AgentResponseGuard.php
src/Agents/AgentValidator.php
tests/Feature/ConversationPersistenceApiTest.php
examples/limen-host/tests/Feature/LimenAiAgentTest.php
```

---

## Out of scope (do not build)

- Non-Laravel PHP support
- Built-in LLM fine-tuning
- Full semantic/hallucination ML model (hosts can wrap APIs in `OutputValidator`)
- Replacing Laravel Gates with custom ACL
- Breaking changes to v1 config keys without deprecation period

---

## Verify before submitting PR

```bash
composer test:release
vendor/bin/pint --dirty
composer audit
php artisan limen-ai:doctor    # from examples/limen-host or testbench app
php artisan limen-ai:validate --strict
```

**Deliverable:** One PR (or release v1.1.0) with tests, docs, and updated `HOST-INTEGRATION-AUDIT.md` showing **10/10** scores.
