# Black-Box Host Guide

Install Limen AI, set env vars, write tools — **no Laravel Gates required** in default mode.

---

## 1. Install

```bash
composer require limen-ai/limen-ai
php artisan limen-ai:install
php artisan migrate
php artisan config:clear
```

---

## 2. Environment

```env
LIMEN_AI_DEFAULT_AGENT=app_assistant
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=sk-...

# Black-box auth (default)
LIMEN_AI_AUTHORIZATION_MODE=simple
LIMEN_AI_REQUIRE_AUTH=false

# Web chat persistence (auto after migrate)
# LIMEN_AI_PERSISTENCE_DRIVER=database
```

Use `LIMEN_AI_AUTHORIZATION_MODE=gates` only when you want Laravel Gates/policies on agents and tools.

---

## 3. Create a tool

```bash
php artisan limen-ai:make:tool GetShipmentStatus --key=get_shipment_status
```

Generated class extends `BaseTool`. Override two methods:

```php
public function authorize(array $input, ToolExecutionContext $context): bool
{
    // Return false = tool will NOT run (safe default pattern)
    return $context->userId() !== null;
}

public function handle(array $input, ToolExecutionContext $context): array
{
    // Business logic only — user_id always from $context, never from $input
    return ['status' => 'ok'];
}
```

Register in `config/limen-ai.php`:

```php
'tools' => [
    'get_shipment_status' => [
        'name' => 'Get Shipment Status',
        'description' => 'Look up a shipment by ID.',
        'class' => App\LimenAi\Tools\GetShipmentStatusTool::class,
        'input_schema' => [
            'shipment_id' => ['type' => 'string', 'required' => true],
        ],
        'confirmation' => false,
    ],
],
'agents' => [
    'app_assistant' => [
        'tools' => ['get_shipment_status'],
        // ...
    ],
],
```

No `authorization.abilities` array needed in simple mode.

---

## 4. Widget

```blade
<x-limen-ai::widget agent="app_assistant" />
```

---

## 5. Verify

```bash
php artisan limen-ai:doctor
php artisan limen-ai:validate
php artisan limen-ai:run app_assistant --user=1 --message="Hello"
```

---

## Authorization modes

| Mode | When to use |
|------|-------------|
| **simple** (default) | Tool `authorize()` methods; no Gate setup |
| **gates** | Enterprise apps with existing Laravel policies |

## Rules (always)

- Never trust `user_id` from LLM tool arguments — use `$context->userId()`
- Return `false` from `authorize()` to block execution
- Use `confirmation: true` in config for destructive tools
- Split many tools across multiple agents — see [scaling-agents-and-tools.md](scaling-agents-and-tools.md)
