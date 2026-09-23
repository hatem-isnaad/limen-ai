#!/usr/bin/env bash
set -euo pipefail

# Remove experimental release tags newer than composer.json version.
#
# Usage:
#   ./.github/scripts/prune-stray-tags.sh          # dry run
#   ./.github/scripts/prune-stray-tags.sh --apply  # delete locally + origin

APPLY=false
if [[ "${1:-}" == "--apply" ]]; then
  APPLY=true
fi

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

VERSION="$(read_composer_version)"
KEEP_TAG="v${VERSION}"

STRAY_TAGS=()
while IFS= read -r tag; do
  [[ -n "$tag" ]] && STRAY_TAGS+=("$tag")
done < <(python3 - <<'PY'
import json
import pathlib
import re
import subprocess

composer = json.loads(pathlib.Path("composer.json").read_text(encoding="utf-8"))
keep_version = composer["version"]

def semver_key(version: str) -> tuple[int, ...]:
    parts = []
    for part in version.split("."):
        match = re.match(r"(\d+)", part)
        if not match:
            raise ValueError(version)
        parts.append(int(match.group(1)))
    return tuple(parts)

keep_key = semver_key(keep_version)
tags = subprocess.check_output(["git", "tag", "-l", "v*"], text=True).splitlines()

for tag in tags:
    version = tag[1:] if tag.startswith("v") else tag
    try:
        key = semver_key(version)
    except ValueError:
        continue

    if tag == f"v{keep_version}":
        continue

    if key > keep_key:
        print(tag)
PY
)

if [[ ${#STRAY_TAGS[@]} -eq 0 ]]; then
  echo "No stray tags newer than ${KEEP_TAG}."
  exit 0
fi

echo "Stray tags to remove (newer than ${KEEP_TAG}):"
for tag in "${STRAY_TAGS[@]}"; do
  echo "  - ${tag}"
done

if [[ "$APPLY" != "true" ]]; then
  echo "Dry run only. Re-run with --apply to delete locally and on origin."
  exit 0
fi

for tag in "${STRAY_TAGS[@]}"; do
  git tag -d "$tag" 2>/dev/null || true
  git push origin ":refs/tags/${tag}" 2>/dev/null || true
done

echo "Done."
