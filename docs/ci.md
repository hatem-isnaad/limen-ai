# Limen AI — CI & Test Matrix

This document describes the continuous integration setup for the package and the test suites that must pass before promoting code to **`main`**.

## Branch gate

CI runs **only on the `stg` branch** (push and pull requests targeting `stg`). See [branching.md](branching.md).

| Event | Branch | CI |
|-------|--------|-----|
| Push | `stg` | Yes |
| Pull request | → `stg` | Yes |
| Push | `main`, `cursor/**`, features | No |

Validate on `stg` before merging to `main` for go-live.

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

After the matrix completes, a **release-gate** job runs `composer test:release` on PHP 8.3.

The PHP 8.3 / Laravel 12 cell optionally emits Clover coverage for downstream reporting.

Manual release validation (optional, before tagging on `main`): [`.github/workflows/release.yml`](../.github/workflows/release.yml) via `workflow_dispatch`.

## Local Commands

```bash
composer test                  # full suite
composer test:architecture     # boundary gates only
composer test:security         # security gates only
composer test:gates            # architecture + security (merge gate)
composer test:release          # full suite + merge gates (pre-go-live)
```

## Merge Gates (Required on `stg`)

These suites **must pass** before merging `stg` → `main`:

| Gate | Suite | Purpose |
|------|-------|---------|
| Boundaries | Architecture | Prevent host-app coupling, UI/runtime leaks, SSRF bypass |
| Security smoke | Security | SSRF, sanitization, redaction, context integrity |
| Coverage map | Architecture (`CriticalCoverageGateTest`) | Security-critical classes have dedicated tests |
| Release gate | `composer test:release` | Full regression + architecture + security |

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

See [TESTING.md](development/TESTING.md) for scenario-level detail.

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
