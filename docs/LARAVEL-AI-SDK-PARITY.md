# Laravel AI SDK — capability parity (Limen AI)

Reference: [Laravel AI SDK docs](https://laravel.com/docs/ai-sdk).

Limen AI **v3.3.2** closes remaining enterprise gaps: Groq/xAI LLMs, Voyage rerank, ElevenLabs STT, Gemini grounded search, MCP SSE, live stream in tool-loop when tools omitted, protocol feature tests, and architecture gate fixes.

## Parity matrix

| SDK capability | Limen v3.3 | Notes |
|----------------|------------|--------|
| Class agents + runtime | ✅ | v2+ |
| Agent middleware (sync + stream) | ✅ | `AgentStepRunner` |
| Live token stream (tool loop) | ✅ | Stream + OpenAI tool-call deltas; tool-call/result SSE meta |
| Vercel AI UI stream | ✅ | Feature-tested HTTP route |
| AG-UI events | ✅ | Feature-tested HTTP route |
| LLM providers | ✅ | OpenAI, Anthropic, Gemini, OpenRouter, **Groq**, **xAI**, Bedrock, Fake |
| Rerank | ✅ | Fake, Cohere, Jina, **Voyage** |
| Images | ✅ | OpenAI, Gemini Imagen, Fake |
| TTS / STT | ✅ | OpenAI, **ElevenLabs**, Fake |
| Web search tools | ✅ | DuckDuckGo, OpenAI, Anthropic, **Gemini Google Search** |
| MCP | ✅ | HTTP, **SSE**, stdio + auto tool registration |
| Files / vector (Knowledge) | ✅ | `Ai::files()` / `vectorStore()` |
| Token usage (no USD) | ✅ | DB + stream finish |

## By design (not `laravel/ai`)

- Package: `limen-ai/limen-ai` (separate from `laravel/ai`).
- OpenAI-hosted file store IDs → Limen Knowledge collections.
- Full CopilotKit graph → host application.

## Env reference

```env
LIMEN_AI_VERCEL_CHAT=true
LIMEN_AI_AG_UI=true
LIMEN_AI_WEB_SEARCH_DRIVER=gemini
GROQ_API_KEY=
XAI_API_KEY=
VOYAGE_API_KEY=
ELEVENLABS_API_KEY=
LIMEN_AI_MCP_ENABLED=true
```

MCP SSE server:

```php
'mcp' => ['servers' => [
    'remote' => ['transport' => 'sse', 'url' => 'https://mcp.example.com/sse'],
]],
```

## Production

```env
LIMEN_AI_DB_PERSISTENCE=true
LIMEN_AI_FAILOVER=groq,openai
LIMEN_AI_RERANK_PROVIDER=voyage
```
