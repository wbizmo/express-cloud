#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

TARGET="express-cloud-stabilization-final-repair.sh"

if [[ ! -d .git || ! -f artisan ]]; then
    echo "Run this script from the Express Cloud repository root."
    exit 1
fi

if [[ ! -f "${TARGET}" ]]; then
    echo "Missing ${TARGET}. Place it in the repository root first."
    exit 1
fi

python3 - <<'PY'
from pathlib import Path

path = Path("express-cloud-stabilization-final-repair.sh")
text = path.read_text()

text = text.replace(
    '    "admin/inventory/transfers" \\\n',
    '',
    1,
)

marker = '''done

section "Full application verification"
'''

replacement = '''done

python3 - <<'PY_TRANSFER'
from pathlib import Path
import re

routes = Path(
    "/tmp/express-cloud-final-repair-routes.txt",
).read_text()

patterns = [
    r"admin/inventory/[^\s]*transfer",
    r"admin\.inventory\.[^\s]*transfer",
    r"InventoryController@(?:transfer|storeTransfer|stockTransfer)",
    r"StockTransfer",
]

if not any(
    re.search(pattern, routes, re.IGNORECASE)
    for pattern in patterns
):
    raise SystemExit(
        "No inventory stock-transfer route was found. "
        "The checker accepts singular, plural, named, and controller-based "
        "transfer routes, so this indicates the route is genuinely absent.",
    )

print("Inventory stock-transfer route detected.")
PY_TRANSFER

section "Full application verification"
'''

if marker in text and "PY_TRANSFER" not in text:
    text = text.replace(marker, replacement, 1)
elif "PY_TRANSFER" in text:
    print("Flexible stock-transfer route verification is already present.")
else:
    raise SystemExit(
        "Could not locate the route-verification completion marker.",
    )

path.write_text(text)
PY

bash -n "${TARGET}"

echo "Patched the stock-transfer route check to detect the actual route shape."
echo "Restarting final stabilization repair..."

exec bash "${TARGET}"
