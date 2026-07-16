#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

TARGET="express-cloud-stabilization-final-repair.sh"

if [[ ! -d .git || ! -f artisan ]]; then
    echo "Run this script from the Express Cloud repository root."
    exit 1
fi

if [[ ! -f "${TARGET}" ]]; then
    echo "Missing ${TARGET}. Place the final repair script in the repository root."
    exit 1
fi

python3 - <<'PY'
from pathlib import Path

path = Path("express-cloud-stabilization-final-repair.sh")
text = path.read_text()

old = 'grep -Eq "abort(_unless)?\\([^;]*404" app/Http/Middleware/RequirePermission.php\n'

new = '''python3 - <<'PY_GUARD'
from pathlib import Path
import re

middleware = Path(
    "app/Http/Middleware/RequirePermission.php",
).read_text()

if "hasPermission" not in middleware:
    raise SystemExit(
        "RequirePermission no longer checks hasPermission().",
    )

if not re.search(
    r"abort(?:_unless)?\\s*\\(.*?404",
    middleware,
    re.DOTALL,
):
    raise SystemExit(
        "RequirePermission does not conceal denial with HTTP 404.",
    )

if re.search(r"abort\\s*\\(\\s*403", middleware):
    raise SystemExit(
        "RequirePermission still exposes HTTP 403.",
    )
PY_GUARD
'''

if old in text:
    text = text.replace(old, new, 1)
elif "PY_GUARD" in text:
    print("Robust authorization guard check is already present.")
else:
    raise SystemExit(
        "Expected brittle authorization grep was not found. "
        "Review the current final repair script.",
    )

path.write_text(text)
PY

bash -n "${TARGET}"

echo "Patched the multiline 404 authorization guard check."
echo "Restarting the final stabilization repair..."

exec bash "${TARGET}"
