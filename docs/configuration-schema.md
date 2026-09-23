# Configuration Schema

Primary file: `config/limen-ai.php`

## Top-Level Keys

| Key | Purpose |
|-----|---------|
| `default_agent` | Fallback agent key |
| `providers` | LLM + embedding provider config |
| `agents` | Agent definitions |
| `tools` | Tool definitions (class or HTTP) |
| `skills` | Skill definitions |
| `workflows` | Workflow definitions |
| `knowledge` | Collections and drivers |
| `memory` | Memory drivers and scopes |
| `authorization` | Default ability mappings |
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
        'required' => true,
        'abilities' => ['support.use'],
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
    'authorization' => [
        'abilities' => ['shipments.read'],
    ],
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
