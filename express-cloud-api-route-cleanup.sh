#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

if [[ ! -f artisan || ! -d routes ]]; then
  echo "[error] Run this script from the Express Cloud repository root."
  exit 1
fi

echo "[1/4] Removing every remaining ApiTokenController route reference"
python3 <<'PY'
from pathlib import Path
import re

route_files = list(Path('routes').glob('*.php'))

for path in route_files:
    text = path.read_text()
    original = text

    # Remove imports, including aliases and differing namespace layouts.
    text = re.sub(
        r'^\s*use\s+[^;]*\\ApiTokenController(?:\s+as\s+\w+)?\s*;\s*\n?',
        '',
        text,
        flags=re.M,
    )

    # Remove single or multiline Route calls whose statement references the controller.
    # Route declarations in Laravel terminate at a semicolon, so this safely removes
    # the complete fluent declaration without touching neighbouring routes.
    text = re.sub(
        r'^\s*Route::(?:(?!;).)*ApiTokenController(?:(?!;).)*;\s*\n?',
        '',
        text,
        flags=re.M | re.S,
    )

    # Remove controller-array declarations that may not begin on the Route line.
    text = re.sub(
        r'^\s*[^\n;]*\[\s*ApiTokenController::class\s*,\s*[\'\"][^\'\"]+[\'\"]\s*\][^;]*;\s*\n?',
        '',
        text,
        flags=re.M,
    )

    # Remove stale string-controller syntax.
    text = re.sub(
        r'^\s*Route::(?:(?!;).)*[\'\"][^\'\"]*ApiTokenController@[^\'\"]+[\'\"](?:(?!;).)*;\s*\n?',
        '',
        text,
        flags=re.M | re.S,
    )

    if text != original:
        path.write_text(text)
        print(f"  cleaned {path}")
PY

echo "[2/4] Checking for unresolved API-token route references"
if grep -RIn --include='*.php' 'ApiTokenController' routes bootstrap app/Providers 2>/dev/null; then
  echo
  echo "[error] ApiTokenController is still referenced above. Nothing else was changed."
  exit 1
fi

echo "[3/4] Clearing Laravel caches without Composer"
XDEBUG_MODE=off php artisan optimize:clear

echo "[4/4] Validating routes"
XDEBUG_MODE=off php artisan route:list >/tmp/express-cloud-route-list.txt
cat /tmp/express-cloud-route-list.txt

echo
echo "[done] Stale ApiTokenController routes are gone. Composer was not invoked."
