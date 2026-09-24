# Limen AI — Architecture Rules

These rules are enforced by code review, documentation, and automated architecture tests.

## Package Boundaries

1. The generic package **must not** reference host application models or services.
   - Forbidden examples: `App\Models\Shipment`, `App\Models\Order`
2. Business logic **must** live in the host application.
3. Limen-specific tools **must** be registered by the host app, not embedded in the package.

## Runtime Boundaries

1. Core Runtime **must not** depend on Blade.
2. Core Runtime **must not** depend directly on Pusher or any broadcaster implementation.
3. Core Runtime **must not** depend directly on config files — use repositories.
4. Core Runtime **must not** call HTTP APIs of the host app for internal business operations.

## Security Boundaries

1. The LLM is **never** a trusted security boundary.
2. Tool authorization **must** occur before execution.
3. Laravel **must** derive user/tenant context from auth — never from LLM output.
4. Secrets **must never** be exposed to the LLM or browser clients.
5. External HTTP tools **must** pass SSRF validation.
6. Sensitive tool arguments/results **must** support redaction in logs.

## Execution Safety

1. Every run **must** have max steps, max tool calls, and timeout limits.
2. Retries **must** respect idempotency for sensitive tools.
3. Long-running execution **must** use queues, not open HTTP requests.
4. Approval state **must** survive request termination.

## Repository Rules

1. Agent/Tool/Skill/Workflow definitions start in config repositories.
2. Database repositories may be added without changing Runtime public API.
3. Version metadata **must** be preserved for future rollback support.

## UI Rules

1. Backend API **must** be usable without Blade.
2. Chat UI **must** consume events/API, not embed runtime logic.
3. Framework messages **must** be translatable (en/ar, RTL/LTR).

## Testing Rules

1. Unit/integration tests **must not** require real LLM APIs by default.
2. Provide fakes: `FakeLlmProvider`, `FakeTool`, `FakeMemory`, etc.
3. Architecture tests **must** fail on boundary violations.

## Documentation Rules

1. Every subsystem **must** have examples before being marked complete.
2. Deferred work **must** be recorded in STATUS.md and IMPLEMENTATION.md.
3. Architectural changes **must** be recorded in DECISIONS.md.

## AI Agent Implementation Rules

1. Read AI_SPEC.md and ARCHITECTURE.md before coding.
2. Work one phase at a time.
3. Never mark incomplete work as complete.
4. Update STATUS.md, PROJECT_MANIFEST.md, IMPLEMENTATION.md, and CHANGELOG.md after each phase.
