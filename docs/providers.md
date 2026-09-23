# LLM & Embedding Providers

Limen AI resolves providers and models entirely from `config/limen-ai.php`. Each agent picks its own `provider` and `model`; the runtime normalizes tool calls across drivers.

## Built-in LLM providers

| Config key | Driver | Use case |
|------------|--------|----------|
| `fake` | Fake | Tests and local development (default) |
| `openai` | OpenAI-compatible | OpenAI API |
| `openrouter` | OpenAI-compatible | OpenRouter (any listed model) |
| `anthropic` | Anthropic Messages API | Claude models |
| `gemini` | Google Gemini API | Gemini models |

Register additional drivers under `providers.drivers` and reference them from a provider block.

## Per-agent provider and model

```php
'agents' => [
    'support_openai' => [
        'model' => env('SUPPORT_OPENAI_MODEL', 'gpt-4.1-mini'),
        'provider' => 'openai',
        // ...
    ],
    'support_claude' => [
        'model' => env('SUPPORT_CLAUDE_MODEL', 'claude-sonnet-4-20250514'),
        'provider' => 'anthropic',
        // ...
    ],
    'support_openrouter' => [
        'model' => env('SUPPORT_OR_MODEL', 'anthropic/claude-3.5-sonnet'),
        'provider' => 'openrouter',
        // ...
    ],
],
```

The agent `model` is passed to the provider on every chat turn. Switch models without code changes — update config or `.env` only.

## Environment variables

| Variable | Provider |
|----------|----------|
| `OPENAI_API_KEY` | `openai` LLM + embeddings |
| `OPENROUTER_API_KEY` | `openrouter` |
| `ANTHROPIC_API_KEY` | `anthropic` |
| `GEMINI_API_KEY` | `gemini` |
| `OPENAI_EMBEDDING_MODEL` | embedding model (default `text-embedding-3-small`) |

## OpenRouter example

OpenRouter uses the OpenAI chat-completions format. Set any OpenRouter model slug on the agent:

```php
'openrouter' => [
    'driver' => 'openrouter',
    'api_key' => env('OPENROUTER_API_KEY'),
    'base_url' => 'https://openrouter.ai/api/v1',
    'headers' => [
        'HTTP-Referer' => env('APP_URL'),
        'X-Title' => env('APP_NAME'),
    ],
],
```

## Embeddings (RAG)

Vector knowledge uses `embeddings.default` and `embeddings.providers`:

```php
'embeddings' => [
    'default' => env('LIMEN_AI_EMBEDDING_PROVIDER', 'fake'),
    'drivers' => [
        'fake' => LimenAi\Providers\Fake\FakeEmbeddingProvider::class,
        'openai' => LimenAi\Providers\OpenAi\OpenAiEmbeddingProvider::class,
    ],
    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        ],
    ],
],
```

Set `knowledge.driver` to `vector` when using real embeddings.

## Add a custom provider

1. Implement `LimenAi\Contracts\Providers\LlmProvider`.
2. Register the class in `providers.drivers`.
3. Add a provider config block with `'driver' => 'your_driver'`.
4. Point agents at the new provider key.

```php
// config/limen-ai.php
'providers' => [
    'drivers' => [
        'acme' => App\LimenAi\Providers\AcmeLlmProvider::class,
    ],
    'acme' => [
        'driver' => 'acme',
        'api_key' => env('ACME_API_KEY'),
        'base_url' => env('ACME_BASE_URL'),
    ],
],
```

Publishable stub: `stubs/custom-llm-provider.stub` (via `limen-ai-stubs` tag).

Custom providers **must** return tool calls in OpenAI-style shape (`tool_calls[].function.name/arguments`) so the runtime parser works unchanged.

## Validation

```bash
php artisan limen-ai:validate
php artisan limen-ai:doctor
```

`AgentValidator` checks that each agent's provider exists and its driver is registered.

## Intentionally post-v1 (not missing)

These are architectural preparations, not incomplete v1 features:

| Item | Status |
|------|--------|
| Database-backed agent repository | Contract ready; config repos used in v1 |
| SaaS multi-tenancy | Future phase |
| MCP tool integrations | Future phase |
| Attachment RAG pipeline | Future phase |
| OpenTelemetry export | Observability hooks exist; exporter deferred |

See [IMPLEMENTATION.md](../IMPLEMENTATION.md) for the full deferral list.
