# Limen Host Integration Guide

This guide explains how the **Limen 3PL** application integrates with the generic Limen AI package without coupling business logic into the package core.

## Architecture

| Layer | Responsibility |
|-------|----------------|
| `limen-ai/limen-ai` | Agents, runtime, tool pipeline, UI, approval, audit |
| Host app (`App\LimenAi\Tools\`) | Business tools: shipment lookup, customer messaging |
| Host app (`App\Services\`) | Domain services backed by Eloquent/models |

The package never imports `App\Models\Shipment`. Tools are registered in host config and resolved through Laravel's container.

## Reference Implementation

See [examples/limen-host/](../examples/limen-host/) for copy-ready classes and a config merge snippet.

Publish Limen demo stubs:

```bash
php artisan vendor:publish --tag=limen-ai-limen-demo
```

## Demo Agent: `limen_3pl`

Configured in `config/limen-ai.php` (see package defaults and `examples/limen-host/config/limen-ai.limen.php`).

| Tool | Approval | Ability |
|------|----------|---------|
| `get_shipment_status` | No | `shipments.view` |
| `send_customer_message` | Yes | `shipments.notify` |

## Scenario 1 — Shipment Lookup

```
User: "Where is shipment 12345?"
  → Auth + agent ability check
  → LLM selects get_shipment_status
  → Tool pipeline authorizes shipments.view
  → ShipmentService returns status
  → LLM summarizes for the operator
  → Chat UI updates (polling or broadcast)
```

## Scenario 2 — Delayed Shipment with Approval

```
User: "Notify customer about delay on shipment 67890"
  → Agent drafts message
  → LLM calls send_customer_message
  → Tool pipeline pauses (confirmation=true)
  → Operator approves in UI
  → Runtime resumes with approval_granted metadata
  → ShipmentService sends notification
  → Audit + observability records captured
```

The `shipment_notify` workflow automates draft → approval → send for batch operations.

## Host App Checklist

1. Implement `App\Contracts\ShipmentService` against your models
2. Register tools in `config/limen-ai.php` with `class` pointing to `App\LimenAi\Tools\*`
3. Implement `authorize()` on each tool class (`BaseTool`) — default mode is `LIMEN_AI_AUTHORIZATION_MODE=simple` (no Gates). For enterprise policy integration, set `gates` and define abilities such as `agents.limen_3pl`, `shipments.view`, `shipments.notify`
4. Set `LIMEN_AI_DEFAULT_AGENT=limen_3pl` (optional)
5. Add `<x-limen-ai::widget agent="limen_3pl" />` to your layout
6. Configure broadcasting for live updates (optional)

## Testing in the Host App

Use `FakeLlmProvider` or HTTP fakes in the host test suite. Package-level integration tests live in `tests/Feature/LimenIntegrationTest.php` and use in-memory shipment stubs — run them when developing the package, not in production Limen.

## Security Notes

- User identity comes from Laravel auth (`RunContextData`), never from LLM tool arguments
- `send_customer_message` always requires human approval (`confirmation => true`)
- Tool abilities are enforced before execution in `ToolPipeline`
