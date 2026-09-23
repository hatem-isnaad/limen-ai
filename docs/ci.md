# Limen AI — CI & Test Matrix

This document describes the recommended continuous integration setup for the package and the test suites that must pass before merge.

## Workflow

GitHub Actions workflow: [`.github/workflows/tests.yml`](../.github/workflows/tests.yml)

### Matrix

| Dimension | Values |
|-----------|--------|
| PHP | 8.2, 8.3 |
| Laravel | 11.x, 12.x |
| Testbench | ^9.0 (L11), ^10.0 (L12) |

Each matrix cell runs:

1. **Unit + Integration + Feature** — functional regression
2. **Architecture** — module and package boundary enforcement
3. **Security** — consolidated security-critical smoke checks

The PHP 8.3 / Laravel 12 cell optionally emits Clover coverage for downstream reporting.

## Local Commands

```bash
composer test                  # full suite
composer test:architecture     # boundary gates only
composer test:security         # security gates only
composer test:gates            # architecture + security (merge gate)
```

## Merge Gates (Required)

These suites **must pass** on every PR:

| Gate | Suite | Purpose |
|------|-------|---------|
| Boundaries | Architecture | Prevent host-app coupling, UI/runtime leaks, SSRF bypass |
| Security smoke | Security | SSRF, sanitization, redaction, context integrity |
| Coverage map | Architecture (`CriticalCoverageGateTest`) | Security-critical classes have dedicated tests |

## Test File Matrix (Summary)

| Area | Unit | Integration | Feature | Architecture | Security |
|------|------|-------------|---------|--------------|----------|
| Agents | ✓ | ✓ | ✓ | ✓ | — |
| Runtime | ✓ | ✓ | ✓ | ✓ | — |
| Tools | ✓ | ✓ | ✓ | ✓ | ✓ |
| Authorization | ✓ | ✓ | ✓ | ✓ | ✓ |
| Security (SSRF/sanitize) | ✓ | ✓ | ✓ | ✓ | ✓ |
| HTTP integrations | ✓ | ✓ | ✓ | ✓ | ✓ |
| Workflows | ✓ | ✓ | ✓ | ✓ | — |
| Queue / Jobs | ✓ | ✓ | ✓ | ✓ | — |
| Broadcasting | ✓ | ✓ | ✓ | ✓ | — |
| Chat UI / HTTP API | ✓ | ✓ | ✓ | ✓ | — |
| Themes | ✓ | — | ✓ | ✓ | — |
| Observability | ✓ | ✓ | ✓ | ✓ | — |
| Developer CLI | ✓ | — | ✓ | — | — |

See [TESTING.md](../TESTING.md) for scenario-level detail.

## Coverage Recommendations

Security-critical paths should maintain dedicated tests (enforced by `CriticalCoverageGateTest`):

- `SsrfUrlValidator`
- `PromptInjectionSanitizer`
- `SensitiveDataRedactor`
- `ToolPipeline` authorization
- `LaravelAuthorizationService`
- `HttpIntegrationValidator`
- `RuntimeLimits`
- `ConversationAccessGuard`

Future improvement: add minimum Clover thresholds for `src/Security/` and `src/Authorization/` in CI once baseline coverage is measured.

## Host App Integration (Out of Package CI)

Limen host integration tests (Phase 21+) run in the consuming application repository, not in this package workflow. The package CI uses Orchestra Testbench with fakes only — no real LLM or Pusher credentials.
