# Black-Box Host Guide

**For Laravel developers who want a working AI chat without wiring Gates, policies, or framework internals.**

Install → set `.env` → add agents/tools/knowledge in config → embed the widget → chat works.

---

## What “black box” means

| You do | Package handles |
|--------|-----------------|
| Set env vars (provider, model, UI theme) | LLM loop, tool pipeline, persistence |
| Define agents in `config/limen-ai.php` | Persona, limits, memory, RAG injection |
| Register tools (`make:tool` + config) | Validation, `authorize()`, audit, approvals |
| Add knowledge documents in config | Retrieval into every agent turn |
| Drop `<x-limen-ai::widget />` in a Blade view | REST API, history, guest mode, realtime |

**Default auth:** `LIMEN_AI_AUTHORIZATION_MODE=simple` — no Laravel Gates. Each tool class has `authorize(): bool`.

---

## 5-minute checklist

```bash
composer require limen-ai/limen-ai
php artisan limen-ai:install
php artisan migrate
php artisan config:clear
```

```env
# .env — minimum for a working widget
LIMEN_AI_DEFAULT_AGENT=app_assistant
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=your-key

LIMEN_AI_AUTHORIZATION_MODE=simple
LIMEN_AI_REQUIRE_AUTH=false

LIMEN_AI_UI_TITLE="Support"
LIMEN_AI_UI_GUEST_ENABLED=true
LIMEN_AI_UI_HISTORY_ENABLED=true
```

```blade
{{-- any layout or welcome view --}}
<x-limen-ai::widget />
```

```bash
php artisan limen-ai:doctor   # also probes Ollama when OPENAI_BASE_URL / API key indicate local Ollama
php artisan limen-ai:validate
```

Open your site → click the chat launcher → ask a question. **No custom PHP required** until you add tools or domain knowledge.

---

## Local Ollama (optional)

```env
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://localhost:11434/v1
LIMEN_AI_APP_ASSISTANT_MODEL=qwen3:8b
```

Ollama must be running (`ollama serve`). Use a **small tool list** per agent (5–10 tools max) with local models.

---

## Step 1 — Define your agent

In `config/limen-ai.php` under `agents`:

```php
'app_assistant' => [
    'name' => 'Support Assistant',
    'provider' => env('LIMEN_AI_PROVIDER', 'fake'),
    'model' => env('LIMEN_AI_APP_ASSISTANT_MODEL', 'gpt-4.1-mini'),
    'instructions' => 'You help users with product questions. Answer from your knowledge. Be concise.',
    'tools' => [],                    // start with none — knowledge-only chat works
    'knowledge' => ['product_help'],  // attach your KB collection (step 2)
    'authorization' => [
        'required' => false,
        'guest_allowed' => true,
        'abilities' => [],
    ],
    'limits' => [
        'max_tool_calls' => 6,
        'max_steps' => 12,
        'max_history_messages' => 20,
    ],
],
```

Or generate a file:

```bash
php artisan limen-ai:make:agent AppAssistant --key=app_assistant
```

---

## Step 2 — Prepare your knowledge base (KB)

Config-driven RAG works **out of the box** — no vector DB required for v1.

Add a collection under `knowledge.collections` and attach it to your agent's `knowledge` array:

```php
'knowledge' => [
    'driver' => env('LIMEN_AI_KNOWLEDGE_DRIVER', 'config'),
    'limit' => 5,
    'collections' => [
        'product_help' => [
            'name' => 'Product Help',
            'description' => 'FAQ and policies for the public widget.',
            'documents' => [
                [
                    'content' => 'Shipping: standard delivery is 3–5 business days. Express is 1–2 days.',
                    'metadata' => ['source' => 'faq', 'topic' => 'shipping'],
                ],
                [
                    'content' => 'Returns: items can be returned within 14 days if unused. Contact support with your order ID.',
                    'metadata' => ['source' => 'faq', 'topic' => 'returns'],
                ],
                [
                    'content' => 'Support hours: Sunday–Thursday 9:00–18:00 Cairo time. Email: support@example.com',
                    'metadata' => ['source' => 'faq', 'topic' => 'contact'],
                ],
                [
                    'content' => 'Account login issues: reset password at /forgot-password. Guest checkout orders use email only.',
                    'metadata' => ['source' => 'faq', 'topic' => 'account'],
                ],
            ],
        ],
    ],
],
```

**Tips**

- Write **short factual chunks** (2–4 sentences each) — not long PDF dumps.
- Cover the questions users actually ask (shipping, pricing, hours, policies).
- Add `metadata` for your own organization; retrieval uses content text.
- For large corpora later, switch to `LIMEN_AI_KNOWLEDGE_DRIVER=vector` — see [providers.md](providers.md).

Full KB guide: [knowledge-base-setup.md](knowledge-base-setup.md)

---

## Step 3 — Add a tool (when Laravel must *do* something)

Only add tools when the model must **execute** your app (lookup order, create ticket, send email).

```bash
php artisan limen-ai:make:tool GetOrderStatus --key=get_order_status
```

```php
// app/LimenAi/Tools/GetOrderStatusTool.php
public function authorize(array $input, ToolExecutionContext $context): bool
{
    return $context->userId() !== null; // guests cannot run this tool
}

public function handle(array $input, ToolExecutionContext $context): array
{
    // Your service / Eloquent logic here
    return ['status' => 'shipped', 'order_id' => $input['order_id']];
}
```

Register in config:

```php
'tools' => [
    'get_order_status' => [
        'name' => 'Get Order Status',
        'description' => 'Look up an order by ID for authenticated users.',
        'class' => App\LimenAi\Tools\GetOrderStatusTool::class,
        'input_schema' => [
            'order_id' => ['type' => 'string', 'required' => true],
        ],
        'confirmation' => false,
    ],
],
```

Add the tool key **only** to agents that need it:

```php
'support_agent' => [
    'tools' => ['get_order_status'],
    // ...
],
```

---

## Step 4 — Widget & theme (env only)

No Blade props required — theme from `.env`:

```env
LIMEN_AI_UI_TITLE="Acme Support"
LIMEN_AI_UI_SUBTITLE="We reply instantly"
LIMEN_AI_UI_WELCOME_MESSAGE="Hi! How can we help?"
LIMEN_AI_UI_DIRECTION=ltr
LIMEN_AI_THEME_MODE=light
LIMEN_AI_THEME_PRIMARY="#2563EB"
LIMEN_AI_UI_GUEST_ENABLED=true
LIMEN_AI_UI_HISTORY_ENABLED=true
LIMEN_AI_UI_RESUME_CONVERSATION=true
```

```blade
<x-limen-ai::widget agent="app_assistant" />
```

Publish UI assets if you customize views:

```bash
php artisan vendor:publish --tag=limen-ai-ui
```

---

## Step 5 — Verify & test

```bash
php artisan limen-ai:doctor
php artisan limen-ai:validate
php artisan limen-ai:agents
php artisan limen-ai:tools

# CLI smoke test (logs in user 1 automatically)
php artisan limen-ai:run app_assistant --user=1 --message="What is your return policy?"

# Interactive agent test
php artisan limen-ai:agent:test app_assistant --message="What are your shipping times?"
```

---

## Multi-agent layout (when you grow)

Do **not** put 20+ tools on one agent. Split by role:

| Agent | Audience | Typical tools |
|-------|----------|---------------|
| `app_assistant` | Public widget | None or 1–2 safe lookups |
| `support_agent` | Staff (auth) | Order/shipment lookups |
| `admin_agent` | Privileged staff | Mutations + `confirmation: true` |

See [scaling-agents-and-tools.md](scaling-agents-and-tools.md) and [examples/limen-host/config/multi-agent.example.php](../examples/limen-host/config/multi-agent.example.php).

---

## Authorization modes

| Mode | When |
|------|------|
| **simple** (default) | `authorize()` on each `BaseTool` — no Gate setup |
| **gates** | Enterprise apps with existing Laravel policies |

```env
LIMEN_AI_AUTHORIZATION_MODE=simple
LIMEN_AI_REQUIRE_AUTH=false
```

---

## Persistence (chat history)

```bash
php artisan migrate   # creates limen_ai_* tables
```

Leave `LIMEN_AI_PERSISTENCE_DRIVER` unset — package **auto-detects** database when tables exist.

---

## What you do NOT need

- Laravel Gates or policies (in simple mode)
- Custom runtime or service provider code
- MCP / dynamic tool plugins
- Semantic quality scoring (optional — implement `OutputValidator` yourself)
- Non-Laravel deployment

---

## Copy-paste env template

```bash
php artisan vendor:publish --tag=limen-ai-env
```

Or see [.env.example](../.env.example) in the package root.

---

## Related docs

| Doc | Purpose |
|-----|---------|
| [host-quickstart.md](host-quickstart.md) | 15-minute walkthrough |
| [knowledge-base-setup.md](knowledge-base-setup.md) | KB collections cookbook |
| [scaling-agents-and-tools.md](scaling-agents-and-tools.md) | Tool limits & agent split |
| [agent-configuration.md](agent-configuration.md) | Persona, limits, output |
| [theming.md](theming.md) | RTL, dark mode, Cairo font |
| [installation.md](installation.md) | Packagist, VCS, path repo |
