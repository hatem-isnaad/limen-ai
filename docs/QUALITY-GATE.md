# Limen AI Quality Gate

Use this checklist before shipping a host integration or package release.

## Automated gates

```bash
composer test:release
vendor/bin/pint --test
composer audit
php artisan limen-ai:doctor --json
php artisan limen-ai:validate --strict
```

## CI smoke test (agent)

```bash
php artisan limen-ai:agent:test app_assistant \
  --message="What is your return policy?" \
  --expect-contains="14 days" \
  --expect-not-contains="password" \
  --min-length=20
```

## First-run host checklist

```bash
php artisan limen-ai:checklist
```

## Production recommendations

- `LIMEN_AI_PERSISTENCE_DRIVER=database` (or migrate + auto-detect)
- `LIMEN_AI_HEURISTIC_VALIDATION=true`
- Guest widgets auto-apply `ThrottleAgentRequests` (set `LIMEN_AI_UI_RATE_LIMIT_ENABLED=true` for staff routes too)
- `LIMEN_AI_SEMANTIC_VALIDATION=true` for LLM judge scoring (Ollama/OpenAI-compatible)
- Mark read-only widget tools with `guest_safe: true`
- Keep agents under `quality.tool_count_warn` (default 15 tools)
