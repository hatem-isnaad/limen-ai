#!/usr/bin/env bash
set -euo pipefail

OUTPUT="${GITHUB_OUTPUT:-/dev/stdout}"

git fetch --tags --force || echo "Warning: unable to fetch tags; using local tag refs"

LAST_TAG="$(git tag -l 'v*' --sort=-v:refname | head -1 || true)"
echo "Latest release tag: ${LAST_TAG:-<none>}"
if [[ -n "$LAST_TAG" ]]; then
  RANGE="${LAST_TAG}..HEAD"
  mapfile -t SUBJECTS < <(git log "${RANGE}" --no-merges --pretty=format:%s)
else
  RANGE=""
  mapfile -t SUBJECTS < <(git log --no-merges --pretty=format:%s)
fi

if [[ ${#SUBJECTS[@]} -eq 0 ]]; then
  echo "No commits found for release analysis"
  echo "published=false" >> "$OUTPUT"
  exit 0
fi

RE_FEAT='^feat(\([^)]+\))?:'
RE_PATCH='^(fix|perf|revert|refactor|build)(\([^)]+\))?:'
RE_NOTES='^- (feat|fix|perf|revert|refactor|build)(\([^)]+\))?:'

BUMP="none"
for subject in "${SUBJECTS[@]}"; do
  commit_prefix="${subject%%:*}"

  if [[ "$subject" == *"BREAKING CHANGE"* ]] || [[ "$commit_prefix" == *"!" ]]; then
    BUMP="major"
    break
  fi

  if [[ "$subject" =~ $RE_FEAT ]]; then
    if [[ "$BUMP" != "major" ]]; then
      BUMP="minor"
    fi
    continue
  fi

  if [[ "$subject" =~ $RE_PATCH ]]; then
    if [[ "$BUMP" == "none" ]]; then
      BUMP="patch"
    fi
  fi
done

if [[ "$BUMP" == "none" ]]; then
  echo "No releasable conventional commits since ${LAST_TAG:-<first release>}"
  echo "Analyzed ${#SUBJECTS[@]} commit subject(s)"
  echo "published=false" >> "$OUTPUT"
  exit 0
fi

echo "Release bump: ${BUMP}"

if [[ -n "$LAST_TAG" ]]; then
  CURRENT="${LAST_TAG#v}"
else
  CURRENT="0.0.0"
fi

IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT"
case "$BUMP" in
  major)
    MAJOR=$((MAJOR + 1))
    MINOR=0
    PATCH=0
    ;;
  minor)
    MINOR=$((MINOR + 1))
    PATCH=0
    ;;
  patch)
    PATCH=$((PATCH + 1))
    ;;
esac

VERSION="${MAJOR}.${MINOR}.${PATCH}"
TAG="v${VERSION}"
DATE="$(date -u +%Y-%m-%d)"

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

echo "Creating GitHub release ${TAG} (${BUMP} bump)"
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
content = re.sub(
    r"## \[Unreleased\]\n\n### Added\n\n",
    "## [Unreleased]\n\n### Added\n\n",
    content,
    count=1,
)
marker = "## [Unreleased]"
if marker not in content:
    raise SystemExit("CHANGELOG.md is missing an [Unreleased] section")

updated = content.replace(
    marker,
    "\n".join(section_lines) + marker,
    1,
)
updated += f"\n[{version}]: https://github.com/hatem-isnaad/limen-ai/releases/tag/v{version}\n"
changelog_path.write_text(updated, encoding="utf-8")
print(f"Updated CHANGELOG.md for {version}")
PY

rm -f "$NOTES_FILE"

echo "published=true" >> "$OUTPUT"
echo "version=${VERSION}" >> "$OUTPUT"
echo "tag=${TAG}" >> "$OUTPUT"
