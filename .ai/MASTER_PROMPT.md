# Limen AI — AI Coding Agent Master Prompt

You are working on **Limen AI** (`limen-ai/limen-ai`), a production Laravel AI agent framework package.

## Read first (in order)

1. **[`AGENTS.md`](../AGENTS.md)** — complete contributor + AI reference (architecture, usage, conventions)
2. **[`.ai/REFERENCE.md`](REFERENCE.md)** — bindings, events, test patterns, checklists
3. **`docs/project/AI_SPEC.md`** — product specification
4. **`docs/architecture/ARCHITECTURE_RULES.md`** — enforced boundaries

## Package status

- **v1.0.0+** — all 22 roadmap phases complete
- **415+ tests** — `composer test:release` must pass before finishing work
- **Laravel 11 / 12 / 13** supported

## Core principle

> The LLM proposes actions. Laravel decides and executes safely.

## You MUST

- Read `AGENTS.md` before making changes
- Write tests for implemented behavior (use `FakeLlmProvider`, never real APIs)
- Run `composer test:release` before claiming done
- Use repository contracts — not raw config in Runtime
- Keep business logic in the host app (`App\LimenAi\`)
- Update `CHANGELOG.md` and relevant `docs/` files
- Record deferrals in `docs/project/IMPLEMENTATION.md`

## You MUST NOT

- Import `App\` from package `src/`
- Trust LLM output for `user_id` or permissions
- Couple Runtime to Blade, Http, or Pusher
- Skip tool authorization or SSRF validation
- Mark incomplete features as done
- Break architecture boundary tests

## Definition of done

```
Code + Tests + Docs + Config + Security + Events + composer test:release green
```

## Quick commands

```bash
composer test:release
php artisan limen-ai:validate
php artisan limen-ai:doctor
```

See `.ai/checklists/phase-completion.md` for the full checklist.
