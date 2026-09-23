# Host Quickstart — Zero to Working Widget

Get Limen AI running in a Laravel host app in ~15 minutes.

---

## 1. Install

```bash
composer require limen-ai/limen-ai
php artisan limen-ai:install
php artisan migrate
php artisan config:clear
```

Persistence **auto-detects database** after migrate when `LIMEN_AI_PERSISTENCE_DRIVER` is unset.

---

## 2. Environment (minimum)

```env
LIMEN_AI_DEFAULT_AGENT=app_assistant
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=your-key

# Local Ollama instead:
# LIMEN_AI_PROVIDER=openai
# OPENAI_API_KEY=ollama
# OPENAI_BASE_URL=http://localhost:11434/v1
# LIMEN_AI_APP_ASSISTANT_MODEL=qwen3:8b
```

Copy the full template: `php artisan vendor:publish --tag=limen-ai-env`

---

## 3. Define one agent

In `config/limen-ai.php`:

```php
'agents' => [
    'app_assistant' => [
        'name' => 'App Assistant',
        'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
        'model' => env('LIMEN_AI_APP_ASSISTANT_MODEL', 'gpt-4.1-mini'),
        'instructions' => 'You are a helpful assistant. Use tools when needed.',
        'tools' => ['example_echo'], // replace with your tools
        'authorization' => [
            'required' => true,
            'guest_allowed' => false,
        ],
        'limits' => [
            'max_tool_calls' => 8,
            'max_steps' => 16,
        ],
    ],
],
```

Generate a custom tool:

```bash
php artisan limen-ai:make:tool GetShipmentStatus
```

Wire the class in `tools.get_shipment_status.class` and add the key to your agent's `tools` array.

---

## 4. Authorize (Gates)

In `AppServiceProvider` or a dedicated provider:

```php
Gate::define('agents.app_assistant', fn ($user) => $user !== null);
Gate::define('shipments.view', fn ($user) => true); // your policy logic
```

Never trust `user_id` from LLM tool arguments — use `auth()` inside the tool via `ToolExecutionContext`.

---

## 5. Add the widget

```blade
{{-- resources/views/layouts/app.blade.php --}}
<x-limen-ai::widget agent="app_assistant" />
```

Publish UI assets if needed:

```bash
php artisan vendor:publish --tag=limen-ai-ui
```

Theme via env (no hardcoded `:theme` prop):

```env
LIMEN_AI_UI_TITLE="My App Support"
LIMEN_AI_UI_DIRECTION=ltr
LIMEN_AI_THEME_PRESET=default
```

---

## 6. Verify

```bash
php artisan limen-ai:doctor
php artisan limen-ai:validate
php artisan limen-ai:agent:test app_assistant --message="Hello"
php artisan limen-ai:run app_assistant --user=1 --message="Hello"
```

Host integration test template: [examples/limen-host/tests/Feature/LimenAiAgentTest.php](../examples/limen-host/tests/Feature/LimenAiAgentTest.php)

---

## 7. Scale safely

Before adding more tools:

1. Read [scaling-agents-and-tools.md](scaling-agents-and-tools.md)
2. Keep **5–15 tools per agent**
3. Split into `app_assistant`, `support_agent`, `admin_agent` as you grow
4. Use `confirmation: true` on sensitive tools
5. Run `limen-ai:validate` after every config change

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Second message → 403 | `php artisan migrate` + `config:clear` |
| Theme ignored | Use `LIMEN_AI_UI_*` env; remove hardcoded Blade `:theme` |
| CLI auth error | Use `--user=1` (CLI logs in via `Auth::loginUsingId`) |
| Raw JSON in chat | Upgrade to v1.0.1+; start a fresh conversation |

Full install guide: [installation.md](installation.md)
