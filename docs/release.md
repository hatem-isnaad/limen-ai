# Release Guide

This document describes how to install, publish, and release **Limen AI** v1.x.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.2 |
| Laravel | ^11.0 or ^12.0 |

## Installation

```bash
composer require limen-ai/limen-ai
```

Publish configuration and assets:

```bash
php artisan vendor:publish --tag=limen-ai-config
php artisan vendor:publish --tag=limen-ai-views
php artisan vendor:publish --tag=limen-ai-assets
```

Run migrations:

```bash
php artisan migrate
```

Validate the environment:

```bash
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

## Host app integration

1. Define tools under `App\LimenAi\Tools\` (see publishable stubs).
2. Register tool classes in `config/limen-ai.php`.
3. Wire authorization abilities and policies in the host app.
4. For the Limen 3PL demo, see [limen-integration.md](limen-integration.md).

Publish Limen demo stubs (optional):

```bash
php artisan vendor:publish --tag=limen-ai-limen-demo
```

## Version policy

Limen AI follows [Semantic Versioning](https://semver.org/):

| Change | Bump |
|--------|------|
| Breaking API or config schema | MAJOR |
| New features, agents, tools (backward compatible) | MINOR |
| Bug fixes, security patches | PATCH |

Package version is declared in `composer.json` and mirrored in `CHANGELOG.md`.

## Pre-release checklist

Run locally or in CI before tagging:

```bash
composer test
composer test:gates
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

Confirm:

- [ ] `CHANGELOG.md` has a dated release section
- [ ] Security-critical tests pass (`composer test:security`)
- [ ] Architecture boundary tests pass (`composer test:architecture`)
- [ ] Default agent and example tools resolve
- [ ] No secrets committed; provider keys remain in `.env`
- [ ] Host-specific code stays in the host app (no `App\` imports in package)

See [SECURITY.md](../SECURITY.md) for the full security model.

## Automatic releases on `main`

Pushes to `main` trigger [semantic-release](https://semantic-release.gitbook.io/) via `.github/workflows/release.yml`.

1. Merge your release branch into `main` (typically `stg` → `main`).
2. Use [Conventional Commits](https://www.conventionalcommits.org/) on merge commits and squash messages so the version bump is correct:

| Commit prefix | Semver bump |
|---------------|-------------|
| `feat:` | MINOR |
| `fix:`, `perf:`, `revert:` | PATCH |
| `feat!:` or `BREAKING CHANGE:` footer | MAJOR |
| `chore:`, `ci:`, `test:` | No release |

3. CI runs `composer test:release`, then semantic-release:
   - Computes the next version from commits since the last tag
   - Updates `CHANGELOG.md`, `composer.json`, and `README.md`
   - Creates GitHub release `vX.Y.Z` with generated notes
   - Pushes the version commit back to `main` (`[skip ci]` avoids loops)

Manual tags are not required. To re-validate a version without releasing, use the **Release** workflow dispatch on GitHub Actions.

## Upgrade notes (v1.0.0)

- First stable release covering phases 01–22.
- Uses config-driven agents; database-backed stores are opt-in via config bindings.
- Fake LLM provider is the default for development; set `LIMEN_AI_PROVIDER=openai` for production.

## Support

- Documentation index: [PROJECT_MANIFEST.md](../PROJECT_MANIFEST.md)
- Issues: https://github.com/hatem-isnaad/limen-ai/issues
