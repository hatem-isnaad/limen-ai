#!/usr/bin/env bash
set -euo pipefail

OUTPUT="${GITHUB_OUTPUT:-/dev/stdout}"
FORCE="${FORCE_RELEASE:-false}"

read_composer_version() {
  python3 - <<'PY'
import json
import pathlib

composer = json.loads(pathlib.Path("composer.json").read_text(encoding="utf-8"))
version = composer.get("version")
if not isinstance(version, str) or not version:
    raise SystemExit("composer.json is missing a string version field")
print(version)
PY
}

collect_commit_subjects() {
  local range="${1:-}"

  SUBJECTS=()

  if [[ -n "$range" ]]; then
    while IFS= read -r subject || [[ -n "$subject" ]]; do
      [[ -n "$subject" ]] && SUBJECTS+=("$subject")
    done < <(git log "${range}" --no-merges --pretty=format:%s)
  else
    while IFS= read -r subject || [[ -n "$subject" ]]; do
      [[ -n "$subject" ]] && SUBJECTS+=("$subject")
    done < <(git log --no-merges --pretty=format:%s)
  fi
}

git fetch --tags --force || echo "Warning: unable to fetch tags; using local tag refs"

VERSION="$(read_composer_version)"
TAG="v${VERSION}"
DATE="$(date -u +%Y-%m-%d)"

echo "Target release from composer.json: ${TAG}"

if git rev-parse -q --verify "refs/tags/${TAG}" >/dev/null 2>&1 || gh release view "$TAG" >/dev/null 2>&1; then
  echo "Release tag ${TAG} already exists; skipping publish"
  echo "published=false" >> "$OUTPUT"
  exit 0
fi

PREV_TAG="$(python3 - <<'PY'
import json
import pathlib
import re
import subprocess

composer = json.loads(pathlib.Path("composer.json").read_text(encoding="utf-8"))
current = composer.get("version")
if not isinstance(current, str):
    raise SystemExit(0)

def semver_key(version: str) -> tuple[int, ...]:
    parts = []
    for part in version.split("."):
        match = re.match(r"(\d+)", part)
        if not match:
            raise ValueError(version)
        parts.append(int(match.group(1)))
    return tuple(parts)

current_key = semver_key(current)
tags = subprocess.check_output(["git", "tag", "-l", "v*", "--merged", "HEAD"], text=True).splitlines()
best_tag = ""
best_key: tuple[int, ...] | None = None

for tag in tags:
    version = tag[1:] if tag.startswith("v") else tag
    try:
        key = semver_key(version)
    except ValueError:
        continue

    if key >= current_key:
        continue

    if best_key is None or key > best_key:
        best_key = key
        best_tag = tag

print(best_tag)
PY
)"
if [[ -n "$PREV_TAG" ]]; then
  RANGE="${PREV_TAG}..HEAD"
  collect_commit_subjects "${RANGE}"
else
  RANGE=""
  collect_commit_subjects
fi

if [[ ${#SUBJECTS[@]} -eq 0 ]]; then
  echo "No commits found for release analysis"
  echo "published=false" >> "$OUTPUT"
  exit 0
fi

RE_FEAT='^feat(\([^)]+\))?:'
RE_PATCH='^(fix|perf|revert|refactor|build)(\([^)]+\))?:'
RE_NOTES='^- (feat|fix|perf|revert|refactor|build)(\([^)]+\))?:'

RELEASABLE=false
for subject in "${SUBJECTS[@]}"; do
  if [[ "$subject" =~ $RE_FEAT ]] || [[ "$subject" =~ $RE_PATCH ]]; then
    RELEASABLE=true
    break
  fi
done

if [[ "$RELEASABLE" != "true" && "$FORCE" != "true" ]]; then
  echo "No releasable conventional commits since ${PREV_TAG:-<first release>}"
  echo "Analyzed ${#SUBJECTS[@]} commit subject(s)"
  echo "published=false" >> "$OUTPUT"
  exit 0
fi

NOTES_FILE="$(mktemp)"
{
  echo "## Limen AI ${TAG}"
  echo
  if [[ -n "$RANGE" ]]; then
    git log "${RANGE}" --pretty=format:'- %s (%h)' | grep -E "$RE_NOTES" || true
  else
    git log --pretty=format:'- %s (%h)' | grep -E "$RE_NOTES" || true
  fi
} > "$NOTES_FILE"

if [[ ! -s "$NOTES_FILE" ]]; then
  if [[ -n "$RANGE" ]]; then
    git log "${RANGE}" --pretty=format:'- %s (%h)' > "$NOTES_FILE"
  else
    git log --pretty=format:'- %s (%h)' > "$NOTES_FILE"
  fi
fi

echo "Creating GitHub release ${TAG}"
gh release create "$TAG" \
  --title "Limen AI ${TAG}" \
  --notes-file "$NOTES_FILE" \
  --target "${GITHUB_SHA:?GITHUB_SHA is required}"

python3 - "$VERSION" "$DATE" "$NOTES_FILE" <<'PY'
import pathlib
import re
import sys

version, date, notes_file = sys.argv[1], sys.argv[2], sys.argv[3]
changelog_path = pathlib.Path("CHANGELOG.md")
notes = pathlib.Path(notes_file).read_text(encoding="utf-8").strip()
bullet_lines = [
    line[2:].strip()
    for line in notes.splitlines()
    if line.startswith("- ")
]

section_lines = [
    f"## [{version}] - {date}",
    "",
    "### Changed",
    "",
]
section_lines.extend(f"- {line}" for line in bullet_lines)
section_lines.append("")

content = changelog_path.read_text(encoding="utf-8")
marker = "## [Unreleased]"
if marker not in content:
    raise SystemExit("CHANGELOG.md is missing an [Unreleased] section")

if f"## [{version}]" in content:
    print(f"CHANGELOG.md already documents {version}; leaving file unchanged")
else:
    updated = content.replace(
        marker,
        "\n".join(section_lines) + marker,
        1,
    )
    link = f"[{version}]: https://github.com/hatem-isnaad/limen-ai/releases/tag/v{version}\n"
    if link.strip() not in updated:
        updated += f"\n{link}"
    changelog_path.write_text(updated, encoding="utf-8")
    print(f"Updated CHANGELOG.md for {version}")
PY

rm -f "$NOTES_FILE"

echo "published=true" >> "$OUTPUT"
echo "version=${VERSION}" >> "$OUTPUT"
echo "tag=${TAG}" >> "$OUTPUT"
