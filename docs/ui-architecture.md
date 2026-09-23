# Chat UI Architecture

## Principles

1. UI is separate from Runtime
2. Backend API works without Blade
3. Realtime updates via Laravel Echo + private channels
4. Themes are configurable and publishable
5. RTL/LTR and Arabic/English supported

## Components

| Component | Tag | Purpose |
|-----------|-----|---------|
| Full chat | `<x-limen-ai::chatbot />` | Embedded chat panel |
| Widget | `<x-limen-ai::widget />` | Floating launcher + drawer |

## Frontend Stack (planned)

- Blade components for structure
- Vanilla JS or Alpine.js for interactivity (decision in Phase 16)
- Laravel Echo for Pusher events
- CSS variables for theming

## API Endpoints (planned)

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/limen-ai/conversations` | Create conversation |
| GET | `/limen-ai/conversations/{id}` | Get conversation + messages |
| POST | `/limen-ai/conversations/{id}/messages` | Send user message (dispatch run) |
| GET | `/limen-ai/runs/{id}` | Run status |
| POST | `/limen-ai/approvals/{id}/approve` | Approve action |
| POST | `/limen-ai/approvals/{id}/reject` | Reject action |
| GET | `/limen-ai/conversations/{id}/stream` | SSE streaming (optional) |

All routes protected by host app middleware + package authorization.

## UI States

- Idle
- Sending
- Agent thinking
- Tool running (show tool name + progress)
- Approval required
- Streaming response
- Error + retry

## Theming

Config under `ui.theme`:

```php
'theme' => [
    'primary' => '#4F46E5',
    'background' => '#FFFFFF',
    'text' => '#111827',
    'radius' => '12px',
    'position' => 'bottom-right',
    'direction' => 'ltr', // or rtl
    'mode' => 'light',
    'title' => 'Limen AI Assistant',
    'welcome_message' => 'How can I help you today?',
],
```

Publishable:

- `resources/views/components/`
- `resources/js/limen-ai/`
- `resources/css/limen-ai/`

## Approval UI

When `ApprovalRequested` event received:

- Show summary of proposed action
- Approve / Reject buttons
- Disable input until resolved

## Security

- No provider API keys in JS
- Echo auth endpoint validates conversation access
- XSS-safe message rendering
