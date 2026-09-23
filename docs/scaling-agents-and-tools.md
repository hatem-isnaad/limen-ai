# Scaling Agents & Tools

Limen AI works well with **many tools across your application** — as long as you do **not** put them all on one agent.

This package is config-driven and Laravel-native. It is **not** a cross-platform agent platform or MCP-style dynamic tool registry. Plan agent boundaries early.

---

## The golden rule

> **Register all tools in config. Expose only the subset each agent needs.**

If a tool is not needed for ~80% of that agent's conversations, remove it from that agent's `tools` array. Use another agent, a workflow step, or a skill that describes when to escalate.

---

## Tool count vs LLM behavior

| Tools per agent | Typical outcome |
|-----------------|-----------------|
| **5–15** | Comfortable for most models (including local 7B–8B) |
| **16–25** | Works with stronger models; watch `limen-ai:validate` warnings |
| **26+** | High wrong-tool rate, large schemas, slower calls — split agents |

**Why:** Every registered tool on an agent sends its JSON schema to the LLM on each turn. More tools = more context, more confusion, more latency.

`php artisan limen-ai:validate` warns when an agent exceeds recommended tool counts.

---

## Recommended multi-agent layout

```text
app_assistant     → 5–8 general tools (public widget, FAQ, light lookups)
support_agent     → ticket/order/shipment tools (authenticated staff)
admin_agent       → privileged mutations + confirmation tools
```

### Example config pattern

```php
'agents' => [
    'app_assistant' => [
        'tools' => ['search_help', 'get_account_summary', 'create_ticket'],
        'authorization' => ['required' => false, 'guest_allowed' => true],
        'limits' => ['max_tool_calls' => 6, 'max_steps' => 12],
    ],
    'support_agent' => [
        'tools' => ['get_shipment_status', 'list_open_tickets', 'add_ticket_note'],
        // gates mode only — in simple mode, use authorize() on each tool class
        'authorization' => ['abilities' => ['agents.support']],
    ],
    'admin_agent' => [
        'tools' => ['send_customer_message', 'refund_order', 'update_shipment'],
        'authorization' => ['abilities' => ['agents.admin']],
        'limits' => ['max_tool_calls' => 10],
    ],
],
```

See [examples/limen-host/config/multi-agent.example.php](../examples/limen-host/config/multi-agent.example.php).

---

## Skills vs tools

| Use | When |
|-----|------|
| **Skill** | Shared instructions, tone, knowledge — no extra tool schemas |
| **Tool** | Something Laravel must execute with auth and validation |

Do not add a tool when a skill instruction is enough. Skills reduce duplication without bloating the tool list.

---

## Workflows vs mega-agents

Use **workflows** when a process has fixed steps (draft → approve → send) instead of teaching one agent to orchestrate everything via tool calls.

```php
'workflows' => [
    'shipment_notify' => [
        'start' => 'draft',
        'steps' => [
            'draft' => ['type' => 'agent', 'agent' => 'support_agent', 'next' => 'approve'],
            'approve' => ['type' => 'approval', 'next' => 'send'],
            'send' => ['type' => 'tool', 'tool' => 'send_customer_message'],
        ],
    ],
],
```

---

## Security at scale

Every tool needs:

1. Config entry in `tools.*`
2. PHP class extending `BaseTool` with `authorize()` + `handle()`
3. Tests for business-critical behavior
4. `confirmation: true` on destructive or customer-facing actions

Optional: Laravel Gates via `LIMEN_AI_AUTHORIZATION_MODE=gates` and `authorization.abilities`.

At 50+ tools this is real ops work. Limen structures execution; **you** own domain logic and policies.

---

## Model selection

| Scenario | Guidance |
|----------|----------|
| Widget agent, 5–10 tools, local Ollama | `qwen3:8b`, `llama3.1:8b` — acceptable with tight tool lists |
| 20+ tools on one agent | Use a stronger cloud model or split agents |
| Admin / approval tools | Prefer reliable models; keep tool count low |

Reply quality is mostly **model + prompts + tool design** — not something the package can fully abstract.

---

## Performance

- Enable **queued runs** for slow tool chains: `LIMEN_AI_QUEUE_AGENT_RUNS=true`
- Set per-agent `limits.max_tool_calls` and `limits.max_steps`
- Use `limits.max_history_messages` to cap context size
- Database persistence (auto-detected after migrate) for multi-request chat

---

## What Limen AI is not

| Expectation | Reality |
|-------------|---------|
| One universal chatbot calling 50 tools | Poor fit — split agents or use workflows |
| Dynamic MCP/plugin tool discovery | Config-driven only |
| Built-in semantic quality scoring | Host implements `OutputValidator` / `OutputModerator` |
| Non-Laravel deployment | Laravel package only |

---

## Verdict matrix

| Your setup | Fit |
|------------|-----|
| Laravel app, 10–30 tools split across 3–5 agents | **Excellent** |
| Monolith, Gates + approvals on sensitive tools | **Excellent** |
| Public guest widget + read-only tools | **Good** with [SECURITY.md](../SECURITY.md) guest hardening |
| One agent, 40 tools, small local LLM | **Poor** — redesign boundaries |
| Plugin ecosystem with runtime tool registration | **Not supported** — use HTTP tools or host wiring |

---

## Related docs

- [host-quickstart.md](host-quickstart.md) — install to first working widget
- [agent-configuration.md](agent-configuration.md) — persona, limits, output hooks
- [limen-integration.md](limen-integration.md) — Limen 3PL reference host
- [SECURITY.md](../SECURITY.md) — threat model and guest mode
