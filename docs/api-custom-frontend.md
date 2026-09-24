# Limen AI — API for custom frontends

Use these HTTP endpoints from your own theme/UI. The package does not require its Blade chat components.

Base prefix: `config('limen-ai.api.route_prefix')` (default `limen-ai`).

Routes load when `LIMEN_AI_API_ENABLED=true` (default), **even if** `LIMEN_AI_UI_ENABLED=false`.

### Sanctum / SPA API

In published `config/limen-ai.php`:

```php
'api' => [
    'middleware' => ['api', 'auth:sanctum'],
    'admin_middleware' => ['api', 'auth:sanctum', 'can:manageLimenAiAgents'],
],
```

## Chat flow

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Package health + feature flags |
| GET | `/agents` | List enabled agents (catalog) |
| GET | `/agents/{key}` | Agent metadata for UI |
| POST | `/conversations` | Create conversation |
| GET | `/conversations/{id}` | Conversation + messages |
| POST | `/conversations/{id}/messages` | Send message (sync or queued run) |
| POST | `/conversations/{id}/messages/stream` | SSE token stream |
| GET | `/runs/{runId}` | Run status, `final_message`, `structured_output`, `usage_summary`, `usage_records` |
| GET | `/runs/{runId}/observability` | Usage / trace report |

### Streaming (SSE)

```http
POST /limen-ai/conversations/{uuid}/messages/stream
Content-Type: application/json

{"message":"Hello"}
```

Events: `data: {"delta":"...","done":false}`.

The final event includes token usage (no dollar cost):

`data: {"delta":"","done":true,"run_id":"...","usage":{"provider":"...","model":"...","input_tokens":0,"output_tokens":0,"total_tokens":0,"message_id":"..."}}`

Optional realtime: subscribe to private channel for `AgentStreamDelta`, `AgentCompleted`, `MessageCreated`.

## Admin: database agent definitions

Requires `LIMEN_AI_DB_AGENTS=true`, migrations, and Gate `manageLimenAiAgents` (configurable via `LIMEN_AI_ADMIN_ABILITY`).

| Method | Path |
|--------|------|
| GET | `/agent-definitions` |
| POST | `/agent-definitions` |
| GET | `/agent-definitions/{key}` |
| PUT/PATCH | `/agent-definitions/{key}` |
| DELETE | `/agent-definitions/{key}` |

Updating an agent bumps `version` and clears definition cache.

## PHP SDK-style usage (no HTTP)

```php
use LimenAi\Facades\LimenAi;
use App\Ai\Agents\SupportAgent;

LimenAi::run('support', $conversationId, $message);
LimenAi::stream('support', $conversationId, $message);

SupportAgent::make()->prompt('Hello');
SupportAgent::make()->stream('Hello');
SupportAgent::make()->queue('Hello'); // async when queue enabled
```

## Usage & webhooks

For production / custom UI, persist conversations, messages, runs, and usage:

```env
LIMEN_AI_DB_PERSISTENCE=true
php artisan migrate
```

Token usage is tracked for every run (`LIMEN_AI_USAGE_TRACKING_ENABLED=true`). Optionally persist rows to `limen_ai_usage_records`:

```env
LIMEN_AI_USAGE_PERSIST_DB=true
```

Completed runs store `usage_summary` on the run record. Streaming runs record provider usage when present; otherwise a rough estimate is stored once (marked `estimated` in usage payload).

Each **assistant reply** in `GET /conversations/{id}` includes a `usage` object on the message:

```json
{
  "role": "assistant",
  "content": "...",
  "usage": {
    "run_id": "...",
    "provider": "openai",
    "model": "gpt-4o-mini",
    "input_tokens": 120,
    "output_tokens": 45,
    "total_tokens": 165,
    "llm_calls": 2,
    "estimated": false
  }
}
```

Synchronous `POST .../messages` also returns the same `usage` block when the run finishes inline (not queued).

Outbound webhooks (optional):

```env
LIMEN_AI_WEBHOOKS_ENABLED=true
LIMEN_AI_WEBHOOK_URLS=https://your-app.test/webhooks/limen-ai
LIMEN_AI_WEBHOOK_TIMEOUT=5
```

Events: `AgentCompleted`, `AgentFailed` — JSON body `{ "event", "payload", "sent_at" }`.

## Disable bundled UI

```env
LIMEN_AI_UI_ENABLED=false
```

Routes and API remain available.
