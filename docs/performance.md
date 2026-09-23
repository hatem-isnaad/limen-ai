# Performance

Limen AI is optimized for typical Laravel request lifecycles. Config-driven agents are resolved once per request when caching is enabled (default).

## Request-scoped agent cache

`DefaultAgentResolver` caches `ResolvedAgent` instances in memory for the duration of a single PHP process/request. This avoids repeated config reads, skill composition, and provider driver resolution when the runtime resumes runs or performs multi-step loops.

```php
// config/limen-ai.php
'performance' => [
    'cache_resolved_agents' => env('LIMEN_AI_CACHE_RESOLVED_AGENTS', true),
],
```

Disable caching only when hot-reloading agent config in long-lived workers without restarting the process.

## Tool schema memoization

`ResolvedAgent::toolSchemas()` builds LLM function-calling schemas once per resolved agent and reuses the result for every chat turn in that run.

## Profiling checklist

Before production traffic:

1. Run agent flows with `LIMEN_AI_TRACE=true` and inspect observability output.
2. Queue long-running agent runs (`LIMEN_AI_QUEUE_AGENT_RUNS=true`) to keep HTTP requests short.
3. Keep knowledge collections scoped; retrieval runs on every user message.
4. Use database-backed repositories for conversations, runs, and memory at scale.

## Related docs

- [Observability](observability.md) — trace IDs, usage tracking, audit export
- [CI](ci.md) — merge gates and test matrix
