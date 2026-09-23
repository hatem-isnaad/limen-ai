# Limen AI — v1.2 Roadmap

**Status:** Released (v1.2.0)  
**Target:** v1.2.0

## Goals

Improve long-conversation reliability and chat UX without changing the v1 config contract.

## Shipped in v1.2 (this branch)

| Feature | Description |
|---------|-------------|
| LLM conversation summarizer | `LlmConversationSummarizer` with cached metadata + recent-message window |
| SSE run streaming | `GET /runs/{id}/stream` for status/delta/completed events |
| UI streaming fallback | Chat client uses EventSource when Echo is unavailable |
| Reverb driver alias | `LIMEN_AI_BROADCAST_DRIVER=reverb` maps to Laravel broadcasting |

## Configuration

```env
# Optional — enable summarization (class name)
LIMEN_AI_CONVERSATION_SUMMARIZER=LimenAi\Conversations\LlmConversationSummarizer
LIMEN_AI_SUMMARY_THRESHOLD=24
LIMEN_AI_SUMMARY_KEEP_RECENT=12

# Streaming (API + UI)
LIMEN_AI_STREAMING_ENABLED=true
LIMEN_AI_UI_STREAMING_ENABLED=true

# Reverb realtime
LIMEN_AI_BROADCAST_DRIVER=reverb
LIMEN_AI_BROADCAST_CONNECTION=reverb
```

## Next (v1.2.x / v1.3)

| Item | Notes |
|------|-------|
| Provider-native token streaming | Extend `LlmProvider` with `chatStream()` for true LLM deltas |
| OpenTelemetry exporter | Wire existing trace/usage hooks |
| Conversation title generator | Auto-title threads for history panel |
| CI coverage floors | Enforce minimum coverage on Security/Authorization |

## Out of scope

- SaaS multi-tenancy builder
- MCP dynamic tool registry
- Built-in hallucination ML models

See [IMPLEMENTATION.md](IMPLEMENTATION.md) deferrals.
