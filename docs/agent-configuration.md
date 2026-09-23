# Agent Configuration — Persona, Quality, Memory & Security

Every agent is fully controlled from `config/limen-ai.php`. No code changes are required to adjust name, tone, language, token limits, memory rules, or output quality.

## Persona block

```php
'persona' => [
    'display_name' => 'Limen 3PL Assistant',
    'tone' => 'professional',
    'language' => 'en',
    'response_style' => 'concise',
    'rules' => [
        'Reference shipment IDs explicitly.',
    ],
    'forbidden' => [
        'Legal advice',
        'Promising delivery dates without tool confirmation',
    ],
],
```

Persona rules are injected into the **system instructions** at resolve time. Language `auto` adds a runtime system addendum based on `RunContext::locale()`.

Validate with:

```bash
php artisan limen-ai:validate
```

## Output quality & token control

```php
'output' => [
    'format' => 'text',
    'max_response_chars' => 3000,
],
'limits' => [
    'temperature' => 0.1,
    'max_tokens' => 1500,
    'max_history_messages' => 24,
    'max_tool_calls' => 8,
    'max_steps' => 16,
    'timeout' => 90,
],
```

Global defaults live under `limen-ai.limits` and `limen-ai.conversations.history_limit`. Per-agent limits override globals.

## Memory (strict & scoped)

```php
'memory' => [
    'conversation' => true,
    'user' => true,
    'agent' => false,
    'limit' => 15,
    'allowed_keys' => [
        'preferred_language',
        'timezone',
        'warehouse_id',
    ],
    'max_value_length' => 512,
],
```

Global strict policy (`limen-ai.memory.strict`):

| Key | Purpose |
|-----|---------|
| `enforce_allowlist` | Reject non-allowlisted keys when agent defines `allowed_keys` |
| `max_key_length` | Reject oversized keys |
| `max_value_length` | Default max value size |
| `allowed_key_pattern` | Regex for safe key names (`snake_case`) |

Host apps store memory via `MemoryService`:

```php
app(MemoryService::class)->rememberUser($userId, 'preferred_language', 'ar', 'limen_3pl');
```

Disallowed keys throw `MemoryPolicyException` before persistence.

## Security layers (automatic)

| Layer | Control |
|-------|---------|
| Auth | Laravel guards + agent `authorization` |
| Tools | Pipeline authorization, validation, approval gates |
| Prompt injection | `ContentSanitizer` on user messages, memory, knowledge |
| SSRF | HTTP tool allowlists + private IP blocking |
| Memory | Key allowlist + length limits + sanitization on recall |
| Output | `max_response_chars` guard on final assistant message |

See [SECURITY.md](../SECURITY.md) for the full threat model.

## Global quality defaults

```php
'quality' => [
    'default_tone' => 'professional',
    'default_language' => 'en',
    'save_tokens' => true,
],
```

## Example: Arabic logistics agent

```php
'limen_ar' => [
    'name' => 'مساعد Limen',
    'model' => 'gpt-4.1-mini',
    'provider' => 'openai',
    'instructions' => '...',
    'persona' => [
        'display_name' => 'مساعد Limen',
        'tone' => 'professional',
        'language' => 'ar',
        'response_style' => 'bullet_points',
    ],
    'limits' => [
        'temperature' => 0.2,
        'max_tokens' => 1000,
        'max_history_messages' => 20,
    ],
],
```

## Related docs

- [providers.md](providers.md) — LLM provider and model selection
- [configuration-schema.md](configuration-schema.md) — full config reference
- [branching.md](branching.md) — stg → main promotion
