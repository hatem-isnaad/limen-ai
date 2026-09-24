# Limen AI — Testing Strategy

## Goals

- No real LLM calls in default test suite
- Enforce architecture boundaries automatically
- Cover security-critical paths (auth, approval, limits)
- Support host app integration testing separately

## Test Suites

| Suite | Purpose |
|-------|---------|
| Unit | Individual classes, DTOs, mappers |
| Integration | Repository + provider + pipeline wiring |
| Feature | HTTP endpoints, commands, end-to-end with fakes |
| Architecture | Namespace/dependency boundary enforcement |
| Security | SSRF, auth bypass attempts, redaction |
| Release readiness | Changelog, composer version, release docs |
| Workflow | Branching, approval, resume |

## Fake Implementations

| Fake | Implements |
|------|------------|
| FakeLlmProvider | LlmProvider |
| FakeEmbeddingProvider | EmbeddingProvider |
| FakeTool | Tool contract / executor |
| FakeMemoryStore | MemoryStore |
| FakeKnowledgeStore | KnowledgeStore / VectorStore |
| FakeRealtimeBroadcaster | RealtimeBroadcaster |
| FakeApprovalStore | ApprovalRepository |

## Architecture Tests

Enforced in `tests/Architecture/`:

| Test | Rule |
|------|------|
| `PackageBoundaryTest` | No host `App\` imports; runtime/provider/tool isolation |
| `ModuleBoundaryTest` | Per-module dependency rules (HTTP, UI, Jobs, Observability) |
| `CriticalCoverageGateTest` | Security-critical classes have matching unit/feature tests |

Examples:

- `LimenAi\Runtime` must not import `Illuminate\View` or `LimenAi\Http\`
- `LimenAi\` must not import `App\`
- No direct `Pusher\` usage outside `Broadcasting\`
- Jobs resolve `AgentRuntime` through the contract only

## Security Suite

`tests/Security/SecurityCriticalMatrixTest.php` provides fast smoke checks for SSRF, sanitization, redaction, and run-context integrity. Feature-level scenarios remain in `tests/Feature/`.

## Critical Test Scenarios

### Runtime

- Max steps enforced
- Max tool calls enforced
- Timeout aborts run safely
- Tool failure handled and surfaced

### Authorization

- Unauthenticated user blocked
- Unauthorized tool blocked
- LLM-provided user_id ignored

### Approval

- Sensitive tool pauses run
- Approve resumes execution
- Reject stops run cleanly

### Idempotency

- Retry does not duplicate side effects

### HTTP Tools

- Private IP blocked
- Disallowed domain blocked

## Testbench Setup

Use `orchestra/testbench` with package service provider registered in test case base class.

## CI

See [docs/ci.md](docs/ci.md) for the full matrix and merge gates.

- PHP 8.2, 8.3
- Laravel 11 + 12 matrix
- Required gates: Unit/Integration/Feature + Architecture + Security
- Local merge gate: `composer test:gates`

## Definition of Done (Testing)

Every implemented feature requires:

- Happy path test
- Primary failure path test
- Authorization test (if applicable)
- Documentation example matching test
