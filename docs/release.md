# Release Guide

This document describes how to install, publish, and release **Limen AI** v1.x.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.2 |
| Laravel | ^11.0 or ^12.0 |

## Installation

See **[installation.md](installation.md)** for all install methods:

- Packagist (`composer require`)
- VCS / Git repository (no Packagist needed)
- Path repository (local development symlink)
- Private Composer registry (Satis, Artifact)
- Monorepo layout

Quick start:

```bash
composer require limen-ai/limen-ai   # or use VCS/path repo — see installation.md
php artisan limen-ai:install
php artisan migrate
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

Copy environment keys from `.env.limen-ai.example` (published) or [`.env.example`](../.env.example).

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

## Tagging a release

1. Merge the release branch to `main`.
2. Ensure CI is green on the merge commit.
3. Create and push an annotated tag:

```bash
git tag -a v1.0.0 -m "Limen AI v1.0.0 — initial stable release"
git push origin v1.0.0
```

4. Create a GitHub release from the tag with notes from `CHANGELOG.md`.

The optional `.github/workflows/release.yml` workflow validates tags and runs the full test matrix.

## Upgrade notes (v1.0.0)

- First stable release covering phases 01–22.
- Uses config-driven agents; database-backed stores are opt-in via config bindings.
- Fake LLM provider is the default for development; set `LIMEN_AI_PROVIDER=openai` for production.

## Support

- Documentation index: [PROJECT_MANIFEST.md](project/PROJECT_MANIFEST.md)
- Issues: https://github.com/hatem-isnaad/limen-ai/issues
