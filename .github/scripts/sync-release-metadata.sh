#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 <version-without-v-prefix>" >&2
  exit 1
fi

VERSION="$1"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

python3 - "$VERSION" "$ROOT/composer.json" <<'PY'
import json
import sys

version, path = sys.argv[1], sys.argv[2]
with open(path, encoding="utf-8") as handle:
    data = json.load(handle)

data["version"] = version

with open(path, "w", encoding="utf-8") as handle:
    json.dump(data, handle, indent=4)
    handle.write("\n")

print(f"Updated composer.json version to {version}")
PY

README="$ROOT/README.md"
RELEASE_LINE="> **Current release:** [v${VERSION}](https://github.com/hatem-isnaad/limen-ai/releases/tag/v${VERSION})"

python3 - "$README" "$RELEASE_LINE" <<'PY'
import re
import sys

path, replacement = sys.argv[1], sys.argv[2]
with open(path, encoding="utf-8") as handle:
    content = handle.read()

pattern = r"> \*\*Current release:\*\* \[v[0-9.]+\]\([^)]+\)"
updated, count = re.subn(pattern, replacement, content, count=1)

if count != 1:
    raise SystemExit(f"Expected to update one README release line, updated {count}")

with open(path, "w", encoding="utf-8") as handle:
    handle.write(updated)

print("Updated README release line")
PY
