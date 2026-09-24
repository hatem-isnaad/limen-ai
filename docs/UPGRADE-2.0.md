# Upgrade guide — 2.0

Limen AI **2.0** adds Laravel AI SDK–style **class-based agents** while keeping **all v1 config agents** working unchanged.

## Backward compatibility

- Existing `config/limen-ai.php` `agents` arrays work as in 1.x.
- `LimenAi::run('example', …)` and HTTP chat API are unchanged.
- Default `AgentRepository` remains `ConfigAgentRepository` (now also resolves `class` entries).

## New in 2.0

| Feature | Usage |
|---------|--------|
| Agent classes | Implement `LimenAi\Ai\Contracts\Agent`, use `LimenAi\Ai\Promptable` |
| Registration | `config('limen-ai.agent_classes')` or `LimenAi::agent('key', MyAgent::class)` |
| Generator | `php artisan make:agent Support` (alias of `limen-ai:make:agent`) |
| Legacy config stub | `php artisan make:agent Support --config` |

## Recommended migration path

1. Stay on config agents until you need class-based DI or SDK-style `->prompt()`.
2. Add new agents as classes under `app/Ai/Agents` and register in `agent_classes`.
3. Optionally move prompts from config to classes over time; do not remove config agents until validated.

## Database-backed agents (2.1)

1. `php artisan migrate`
2. Set `LIMEN_AI_DB_AGENTS=true` in `.env`
3. Optional: `php artisan limen-ai:agents:import-config --force`
4. Edit rows in `limen_ai_agent_definitions` (or your admin UI). Bump `version` after prompt changes if using cache.

Config agents remain the default source when the same key exists in both places (`definition_sources` default: `config`, then `database`).

## Streaming for custom UI (2.2)

No Blade/Livewire changes — use HTTP SSE or events:

```http
POST /limen-ai/conversations/{uuid}/messages/stream
Content-Type: application/json

{"message":"Hello"}
```

Response: `text/event-stream` lines `data: {"delta":"...","done":false}`.

PHP:

```php
foreach (LimenAi::stream('stream_agent', $conversationId, 'Hi') as $chunk) {
    echo $chunk->delta;
}
```

Agents with tools require `LIMEN_AI_STREAMING_WITH_TOOLS=true` or use non-streaming `run()`.
