# Installation Guide

Limen AI is a **Laravel package** — install it into an existing Laravel 11, 12, or 13 application. You do **not** need to publish to Packagist to use it.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.2 |
| Laravel | ^11.0, ^12.0, or ^13.0 |
| Composer | 2.x |

---

## Method 1 — Packagist (recommended for production)

When the package is published on [Packagist](https://packagist.org):

```bash
composer require limen-ai/limen-ai
```

Then publish assets:

```bash
php artisan limen-ai:install
php artisan migrate
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

---

## Method 2 — VCS repository (GitHub / GitLab / Bitbucket)

Install directly from a Git repository without Packagist. Add to your **host app** `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/hatem-isnaad/limen-ai.git"
        }
    ],
    "require": {
        "limen-ai/limen-ai": "^1.0"
    }
}
```

Install a specific branch or tag:

```json
"limen-ai/limen-ai": "dev-main"
```

```json
"limen-ai/limen-ai": "1.0.0"
```

Then run:

```bash
composer update limen-ai/limen-ai
php artisan limen-ai:install
```

**SSH remotes:**

```json
{
    "type": "vcs",
    "url": "git@github.com:hatem-isnaad/limen-ai.git"
}
```

---

## Method 3 — Path repository (local development)

Best for contributing to the package or testing changes before release. Clone the repo next to your Laravel app:

```
projects/
├── my-laravel-app/
└── limen-ai/          ← this package
```

In your **host app** `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../limen-ai",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "limen-ai/limen-ai": "@dev"
    }
}
```

```bash
composer update limen-ai/limen-ai
php artisan limen-ai:install
```

Changes in `../limen-ai/src/` are reflected immediately via symlink.

**Monorepo layout** — same approach with a nested path:

```json
{
    "type": "path",
    "url": "./packages/limen-ai",
    "options": { "symlink": true }
}
```

---

## Method 4 — Private Composer registry

For teams using Satis, Artifact, or a private Packagist instance:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.your-company.com"
        }
    ],
    "require": {
        "limen-ai/limen-ai": "^1.0"
    }
}
```

Publish a release artifact:

```bash
composer archive --format=zip --dir=dist
# Upload dist/limen-ai-limen-ai-1.0.0.zip to your private registry
```

---

## Method 5 — GitHub Packages / Composer VCS with token

For private GitHub repos, configure auth in `auth.json` (never commit this file):

```json
{
    "github-oauth": {
        "github.com": "ghp_your_token_here"
    }
}
```

Then use Method 2 with your private repository URL.

---

## Method 6 — Copy into vendor (last resort)

Only for air-gapped environments without Composer network access:

1. Copy the package into `packages/limen-ai/` inside your app
2. Use Method 3 (path repository) pointing at that folder
3. Run `composer dump-autoload`

Do not edit files directly under `vendor/` — they are overwritten on `composer update`.

---

## Post-install steps (all methods)

### 1. Publish configuration and assets

```bash
php artisan limen-ai:install
```

Or publish individually:

```bash
php artisan vendor:publish --tag=limen-ai-config
php artisan vendor:publish --tag=limen-ai-env      # → .env.limen-ai.example
php artisan vendor:publish --tag=limen-ai-migrations
php artisan vendor:publish --tag=limen-ai-ui       # views + assets
php artisan vendor:publish --tag=limen-ai-stubs    # generator stubs
```

### 2. Environment variables

Copy keys from the published `.env.limen-ai.example` into your app `.env`, or reference the package [`.env.example`](../.env.example).

Minimum for local development:

```env
LIMEN_AI_DEFAULT_AGENT=example
LIMEN_AI_PROVIDER=fake
```

### 3. Database

```bash
php artisan migrate
```

### 4. Validate

```bash
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

### 5. Optional — Limen 3PL demo stubs

```bash
php artisan vendor:publish --tag=limen-ai-limen-demo
```

See [limen-integration.md](limen-integration.md).

---

## Verify installation

```bash
php artisan limen-ai:list
php artisan limen-ai:agent:test example
```

Drop the chat widget into a Blade view:

```blade
<x-limen-ai::chatbot agent="example" />
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `Class LimenAi\... not found` | Run `composer dump-autoload` |
| Provider not registered | Ensure `composer.json` has Laravel auto-discovery; run `php artisan package:discover` |
| Config out of date | `php artisan vendor:publish --tag=limen-ai-config --force` |
| Path repo not updating | `composer update limen-ai/limen-ai --prefer-source` |

---

## Next steps

- [providers.md](providers.md) — configure OpenAI, Anthropic, Gemini, or OpenRouter
- [agent-configuration.md](agent-configuration.md) — persona and quality settings
- [artisan-command-map.md](artisan-command-map.md) — CLI reference
- [index.html](index.html) — interactive documentation hub
