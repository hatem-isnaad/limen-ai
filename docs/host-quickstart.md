# Host Quickstart — Zero to Working Chat

**~15 minutes.** Install, set env, add knowledge, embed widget — chat replies automatically.

> Full black-box guide: [black-box-host-guide.md](black-box-host-guide.md)  
> Knowledge base cookbook: [knowledge-base-setup.md](knowledge-base-setup.md)

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

## 2. Environment (copy & edit)

```env
LIMEN_AI_DEFAULT_AGENT=app_assistant
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=your-key

# Black-box — no Gates required
LIMEN_AI_AUTHORIZATION_MODE=simple
LIMEN_AI_REQUIRE_AUTH=false

# Widget
LIMEN_AI_UI_TITLE="Support"
LIMEN_AI_UI_GUEST_ENABLED=true
LIMEN_AI_UI_HISTORY_ENABLED=true
```

**Ollama locally:**

```env
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://localhost:11434/v1
LIMEN_AI_APP_ASSISTANT_MODEL=qwen3:8b
```

Full template: `php artisan vendor:publish --tag=limen-ai-env`

---

## 3. Agent + knowledge (so chat has answers)

Edit `config/limen-ai.php`:

```php
'agents' => [
    'app_assistant' => [
        'name' => 'Support',
        'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
        'model' => env('LIMEN_AI_APP_ASSISTANT_MODEL', 'gpt-4.1-mini'),
        'instructions' => 'Answer from your knowledge. Be helpful and concise.',
        'tools' => [],
        'knowledge' => ['product_help'],
        'authorization' => [
            'required' => false,
            'guest_allowed' => true,
            'abilities' => [],
        ],
        'limits' => ['max_tool_calls' => 6, 'max_steps' => 12],
    ],
],

'knowledge' => [
    'driver' => 'config',
    'limit' => 5,
    'collections' => [
        'product_help' => [
            'name' => 'Product Help',
            'documents' => [
                ['content' => 'Shipping takes 3–5 business days.'],
                ['content' => 'Returns accepted within 14 days.'],
            ],
        ],
    ],
],
```

Add your real FAQ content — see [knowledge-base-setup.md](knowledge-base-setup.md).

---

## 4. Widget (one line)

```blade
<x-limen-ai::widget />
```

Uses `LIMEN_AI_DEFAULT_AGENT` and theme from `.env`.

---

## 5. Tools (optional — when Laravel must execute)

```bash
php artisan limen-ai:make:tool GetOrderStatus --key=get_order_status
```

```php
public function authorize(array $input, ToolExecutionContext $context): bool
{
    return $context->userId() !== null;
}
```

Wire class in `tools.*.class` and add key to agent `tools` array. See [black-box-host-guide.md](black-box-host-guide.md).

---

## 6. Verify

```bash
php artisan limen-ai:doctor
php artisan limen-ai:validate
php artisan limen-ai:agent:test app_assistant --message="What is your return policy?"
```

Open your site → chat widget → ask the same question.

---

## 7. Scale later

1. [scaling-agents-and-tools.md](scaling-agents-and-tools.md) — split `app_assistant` / `support_agent` / `admin_agent`
2. Keep **5–15 tools per agent**
3. `confirmation: true` on destructive tools
4. Run `limen-ai:validate` after every config change

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Second message → 403 | `php artisan migrate` + `config:clear` |
| Generic/wrong answers | Add KB documents; test with `agent:test` |
| Theme ignored | Use `LIMEN_AI_UI_*` env; remove hardcoded Blade `:theme` |
| Raw JSON in chat | Upgrade to v1.0.1+; start fresh conversation |
| Tool blocked | Check `authorize()` returns `true` for that user |

Full install: [installation.md](installation.md)
