# Limen AI — Observability Guide

## Overview

Limen AI records **audit logs**, **usage metrics**, and **trace correlation IDs** for agent runs and tool executions. Data is redacted via `SensitiveDataRedactor` before logging.

## Configuration

```php
'observability' => [
    'audit_enabled' => env('LIMEN_AI_AUDIT_ENABLED', true),
    'usage_tracking_enabled' => env('LIMEN_AI_USAGE_TRACKING_ENABLED', true),
    'trace_enabled' => env('LIMEN_AI_TRACE_ENABLED', true),
],
```

## Trace Correlation

Each agent run receives:

- `trace_id` — shared across run, LLM calls, and tool spans
- `span_id` — root span for the run
- Child tool spans include `parent_span_id` linking back to the run span

## Run Report API

```
GET /limen-ai/runs/{runId}/observability
```

Returns trace IDs, audit entries, and usage records for the run (requires conversation access).
