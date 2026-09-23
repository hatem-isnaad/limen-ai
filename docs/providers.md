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

## Example agents (one per provider)

The package ships ready-to-use example agents in `config/limen-ai.php`:

| Agent key | Provider | Default model | Env model key |
|-----------|----------|---------------|---------------|
| `example` | `fake` | `gpt-4.1-mini` | `LIMEN_AI_EXAMPLE_MODEL` |
| `example_openai` | `openai` | `gpt-4.1-mini` | `LIMEN_AI_OPENAI_MODEL` |
| `example_anthropic` | `anthropic` | `claude-sonnet-4-20250514` | `LIMEN_AI_ANTHROPIC_MODEL` |
| `example_gemini` | `gemini` | `gemini-2.0-flash` | `LIMEN_AI_GEMINI_MODEL` |
| `example_openrouter` | `openrouter` | `anthropic/claude-3.5-sonnet` | `LIMEN_AI_OPENROUTER_MODEL` |

Use in Blade or API:

```blade
<x-limen-ai::chatbot agent="example_openai" />
```

Set the matching API key in `.env`, then run `php artisan limen-ai:validate`.

## Per-agent provider and model

Each agent picks its own `provider` and `model`. The model is passed to the provider on every chat turn — switch models without code changes by updating config or `.env` only.

## Environment variables

Copy from the published template:

```bash
php artisan vendor:publish --tag=limen-ai-env
# merges into .env.limen-ai.example at project root
```

Or see `.env.example` in the package repository for the full key list.

| Variable | Purpose |
|----------|---------|
| `OPENAI_API_KEY` | `openai` LLM + embeddings |
| `OPENROUTER_API_KEY` | `openrouter` |
| `ANTHROPIC_API_KEY` | `anthropic` |
| `GEMINI_API_KEY` | `gemini` |
| `LIMEN_AI_OPENAI_MODEL` | Model for `example_openai` |
| `LIMEN_AI_ANTHROPIC_MODEL` | Model for `example_anthropic` |
| `LIMEN_AI_GEMINI_MODEL` | Model for `example_gemini` |
| `LIMEN_AI_OPENROUTER_MODEL` | Model for `example_openrouter` |
| `OPENAI_EMBEDDING_MODEL` | Embedding model (default `text-embedding-3-small`) |

## Ollama / local OpenAI-compatible servers

Ollama exposes an OpenAI-compatible API. Point the `openai` provider at your local server:

```env
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://localhost:11434/v1
LIMEN_AI_EXAMPLE_MODEL=qwen3:8b
```

Use the model name exactly as `ollama list` shows it. The API key can be any non-empty string — Ollama does not validate it, but Limen AI requires a value.

For agents other than `example`, set the matching `LIMEN_AI_*_MODEL` env key or override `model` in the agent config block.

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
| Attachment RAG pipeline | Enabled when `knowledge.driver=vector` and `attachments.rag.enabled=true` |
| OpenTelemetry export | Observability hooks exist; exporter deferred |

See [IMPLEMENTATION.md](project/IMPLEMENTATION.md) for the full deferral list.
