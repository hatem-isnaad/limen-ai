# Configuration Schema

Primary file: `config/limen-ai.php`

## Top-Level Keys

| Key | Purpose |
|-----|---------|
| `default_agent` | Fallback agent key |
| `providers` | LLM provider config + `drivers` registry (see [providers.md](providers.md)) |
| `embeddings` | Embedding provider config for vector RAG |
| `performance` | Request-scoped resolver cache toggles |
| `agents` | Agent definitions |
| `tools` | Tool definitions (class or HTTP) |
| `skills` | Skill definitions |
| `workflows` | Workflow definitions |
| `knowledge` | Collections and drivers |
| `memory` | Memory drivers and scopes |
| `persistence` | `driver`, `auto_detect` — database auto-detect after migrate |
| `authorization` | `mode` (`simple`|`gates`), guest validator, context user match |
| `responses` | User-facing default messages |
| `limits` | Global execution limits |
| `broadcasting` | Realtime adapter config |
| `queue` | Queue connection/name |
| `observability` | Logging, usage tracking |
| `security` | SSRF, redaction, injection settings |
| `ui` | Chat widget defaults and theme |
| `paths` | Host app generation paths |

## Agent Definition Schema (conceptual)

```php
'customer_support' => [
    'name' => 'Customer Support',
    'description' => '...',
    'model' => 'gpt-4.1-mini',
    'provider' => 'openai',
    'instructions' => '...',
    'skills' => ['shipment_support'],
    'tools' => ['get_shipment', 'search_orders'],
    'knowledge' => ['shipping_policy'],
    'memory' => [
        'conversation' => true,
        'user' => true,
    ],
    'authorization' => [
        'required' => env('LIMEN_AI_REQUIRE_AUTH', false),
        'abilities' => [], // optional in simple mode — use tool authorize() instead
        'guest_allowed' => false,
    ],
    'output' => [
        'format' => 'text',
    ],
    'limits' => [
        'max_tool_calls' => 10,
        'max_steps' => 20,
        'timeout' => 60,
    ],
    'version' => '1.0.0',
],
```

## Tool Definition Schema (class-based)

```php
'get_shipment' => [
    'name' => 'Get Shipment',
    'description' => 'Retrieve shipment status by ID',
    'class' => App\LimenAi\Tools\GetShipmentStatusTool::class,
    'input_schema' => [
        'shipment_id' => ['type' => 'string', 'required' => true],
    ],
    // authorization.abilities optional in LIMEN_AI_AUTHORIZATION_MODE=simple (default)
    'confirmation' => false,
    'timeout' => 10,
    'rate_limit' => '60,1',
    'idempotency' => false,
],
```

## HTTP Tool Schema (future Phase 13)

```php
'external_lookup' => [
    'connection' => 'partner_api',
    'method' => 'GET',
    'path' => '/records/{id}',
    'input_schema' => [...],
    'request_mapping' => [...],
    'response_mapping' => [...],
],
```

## Authorization

```php
'authorization' => [
    'mode' => env('LIMEN_AI_AUTHORIZATION_MODE', 'simple'), // simple | gates
    'enforce_context_user_match' => true,
    'guest' => [
        'validator' => LimenAi\Authorization\CacheGuestSessionValidator::class,
        'cache_prefix' => 'limen-ai:guest:',
    ],
],
```

| Mode | Behavior |
|------|----------|
| `simple` (default) | No Gates when `abilities` empty; use `BaseTool::authorize()` |
| `gates` | Laravel `Gate::check()` on `authorization.abilities` |

## Quality / tool limits

```php
'quality' => [
    'tool_count_warn' => 15,
    'tool_count_critical' => 25,
],
```

`php artisan limen-ai:validate` warns when agents exceed these counts.

## Response Fallback Order

1. Tool-level override
2. Agent-level override
3. Global config override
4. Framework default lang string

## Localization

Lang files:

- `lang/en/responses.php`
- `lang/ar/responses.php`

Config values may reference lang keys:

```php
'unauthorized' => 'limen-ai::responses.unauthorized',
```
