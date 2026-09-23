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

## Architecture Tests (Phase 20, scaffold in Phase 01)

Examples:

- `LimenAi\Runtime` must not import `Illuminate\View`
- `LimenAi\` must not import `App\`
- No direct `Pusher\` usage outside `Broadcasting\Adapters`

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

## CI (Future)

- PHP 8.2, 8.3
- Laravel 11 + 12 matrix
- PHPUnit + architecture suite

## Definition of Done (Testing)

Every implemented feature requires:

- Happy path test
- Primary failure path test
- Authorization test (if applicable)
- Documentation example matching test
