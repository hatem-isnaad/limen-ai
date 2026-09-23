# Branching & Deployment Flow

Limen AI uses a two-stage promotion model: **`stg` validates**, **`main` goes live**.

```
feature / cursor/*  ──PR──▶  stg  ──CI──▶  (green)  ──PR──▶  main  ──▶  tag / release
                              ▲                              ▲
                         CI runs here                  no CI on push
```

## Branches

| Branch | Role | CI on push |
|--------|------|------------|
| `cursor/*`, feature branches | Development | No |
| **`stg`** | Pre-production staging; must pass full test matrix before promotion | **Yes** |
| **`main`** | Production / go-live; stable releases only | No |

## Workflow

1. Develop on a feature or `cursor/*` branch.
2. Open a PR into **`stg`**.
3. Push to **`stg`** (or merge the PR) — GitHub Actions runs the full test matrix + release gate.
4. After CI is green, open a PR from **`stg`** → **`main`**.
5. Merge to **`main`** for go-live. Tag releases from `main` after validation on `stg`.

Pushes and PRs targeting **`main`** or **`cursor/**`** do **not** trigger CI. All automated gates run on **`stg`** only.

## Creating or resetting `stg`

Bootstrap `stg` from current `main`:

```bash
git fetch origin
git checkout main
git pull origin main
git checkout -B stg
git push -u origin stg
```

Keep `stg` close to `main` after each go-live merge, or reset `stg` to `main` when starting a new release cycle.

## Local validation (any branch)

```bash
composer test:gates
composer test:release
php artisan limen-ai:doctor
php artisan limen-ai:validate
```

## Related docs

- [ci.md](ci.md) — test matrix and merge gates
- [release.md](release.md) — tagging and release checklist
