# Knowledge Base Setup

Prepare FAQ and policy content so your agent **answers from real data** — not hallucinations.

Config-driven knowledge works immediately after install. No vector database required for most apps.

---

## How it works

1. You define **collections** in `config/limen-ai.php` → `knowledge.collections`
2. Each agent lists collection keys in its `knowledge` array
3. On every user message, relevant chunks are retrieved and injected into the LLM context
4. The model answers using that context (still validate critical facts in tools when needed)

---

## Import from CSV or JSON

After `php artisan limen-ai:install`, import bulk FAQ content without hand-editing PHP arrays:

```bash
# JSON: [{"content": "...", "metadata": {"topic": "shipping"}}]
php artisan limen-ai:import:knowledge storage/faq.json --collection=product_help

# CSV: content column required; other columns become metadata
php artisan limen-ai:import:knowledge storage/faq.csv --collection=product_help --append
```

Imported collections are written to `config/limen-ai-knowledge.php` and merged into `knowledge.collections` on boot.

---

## Minimal example

```php
// config/limen-ai.php

'knowledge' => [
    'driver' => env('LIMEN_AI_KNOWLEDGE_DRIVER', 'config'),
    'limit' => 5,  // max chunks per turn
    'collections' => [
        'product_help' => [
            'name' => 'Product Help',
            'description' => 'Public FAQ for the support widget.',
            'documents' => [
                [
                    'content' => 'We ship to Saudi Arabia, UAE, and Egypt. Standard delivery: 3–5 business days.',
                    'metadata' => ['topic' => 'shipping'],
                ],
                [
                    'content' => 'Free shipping on orders over 500 SAR.',
                    'metadata' => ['topic' => 'shipping'],
                ],
            ],
        ],
    ],
],

'agents' => [
    'app_assistant' => [
        // ...
        'knowledge' => ['product_help'],
        'tools' => [],  // KB-only agent — no tools needed for FAQ
    ],
],
```

---

## Writing good documents

| Do | Avoid |
|----|-------|
| Short paragraphs (2–4 sentences) | Pasting entire PDFs as one document |
| One topic per document | Mixing unrelated topics in one chunk |
| Concrete facts (hours, prices, URLs) | Vague marketing copy |
| Bilingual content if users ask in AR/EN | Relying on the model to invent policies |

**Example topics for a 3PL / logistics app**

```php
'documents' => [
    ['content' => 'Shipment statuses: pending, in_transit, delayed, delivered, cancelled.', 'metadata' => ['topic' => 'statuses']],
    ['content' => 'Track shipments in the customer portal with your order email.', 'metadata' => ['topic' => 'tracking']],
    ['content' => 'Delays due to customs may add 2–5 days. We notify customers by SMS when status changes.', 'metadata' => ['topic' => 'delays']],
    ['content' => 'Warehouse hours: Sun–Thu 8:00–17:00. No pickups on Friday.', 'metadata' => ['topic' => 'warehouse']],
],
```

---

## Skills vs knowledge

| Use | When |
|-----|------|
| **Knowledge** | Facts, FAQ, policies — retrieved automatically |
| **Skills** | Tone, rules, procedure instructions (no extra JSON schemas) |

```php
'skills' => [
    'general_assistance' => [
        'name' => 'General Assistance',
        'instructions' => 'Be friendly and concise. If you do not know, say so and suggest contacting support.',
        'tools' => [],
        'knowledge' => ['product_help'],
    ],
],
```

Attach skills to agents via `'skills' => ['general_assistance']`.

---

## Multiple collections

```php
'collections' => [
    'product_help' => [ /* FAQ */ ],
    'internal_ops' => [
        'name' => 'Internal Operations',
        'documents' => [
            ['content' => 'Escalate billing disputes to finance@example.com.', 'metadata' => ['audience' => 'staff']],
        ],
    ],
],

'agents' => [
    'app_assistant' => ['knowledge' => ['product_help']],
    'support_agent' => ['knowledge' => ['product_help', 'internal_ops']],
],
```

Public agents should **not** attach staff-only collections.

---

## Vector RAG (optional, larger corpora)

When config collections are too small or you need semantic search over thousands of docs:

```env
LIMEN_AI_KNOWLEDGE_DRIVER=vector
LIMEN_AI_EMBEDDING_PROVIDER=openai
```

See [providers.md](providers.md) for embedding setup and `limen-ai:make:knowledge` for custom retrievers.

---

## Test your KB

```bash
php artisan limen-ai:agent:test app_assistant --message="What is your return policy?"
php artisan limen-ai:agent:test app_assistant --message="How long does shipping take?" --expect-contains="business days"
```

If answers are wrong, add or rewrite documents — do not only change the system prompt.

---

## Related

- [black-box-host-guide.md](black-box-host-guide.md) — full install path
- [agent-configuration.md](agent-configuration.md) — persona and limits
- [SECURITY.md](../SECURITY.md) — untrusted content sanitization on injected knowledge
