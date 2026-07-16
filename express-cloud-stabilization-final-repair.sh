#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SCRIPT_PATH="$(readlink -f "$0")"
LOG_FILE="/tmp/express-cloud-stabilization-final-repair-$(date -u +%Y%m%dT%H%M%SZ).log"
SKIP_PUSH="${SKIP_PUSH:-0}"

exec > >(tee -a "${LOG_FILE}") 2>&1

fail() {
    local exit_code=$?
    local line_no="${1:-unknown}"

    echo
    echo "============================================================"
    echo "EXPRESS CLOUD STABILIZATION FINAL REPAIR FAILED"
    echo "Line: ${line_no}"
    echo "Exit code: ${exit_code}"
    echo "Log retained at: ${LOG_FILE}"
    echo "============================================================"
    exit "${exit_code}"
}

trap 'fail "$LINENO"' ERR

section() {
    echo
    echo "============================================================"
    echo "$1"
    echo "============================================================"
}

assert_file() {
    [[ -s "$1" ]] || {
        echo "Required file missing or empty: $1"
        exit 1
    }
}

section "Final stabilization repair preflight"

if [[ ! -d .git || ! -f artisan ]]; then
    echo "Run this script from the Express Cloud repository root."
    exit 1
fi

for command in php composer npm python3 git; do
    command -v "${command}" >/dev/null 2>&1 || {
        echo "Required command missing: ${command}"
        exit 1
    }
done

export XDEBUG_MODE=off

assert_file tests/Feature/Stabilization/PermissionConcealmentTest.php
assert_file app/Http/Middleware/RequirePermission.php

section "Replacing the invalid final-class mock test"

cat > tests/Feature/Stabilization/PermissionConcealmentTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Feature\Stabilization;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PermissionConcealmentTest extends TestCase
{
    #[Test]
    public function denied_permissions_are_concealed_as_not_found(): void
    {
        $middleware = file_get_contents(
            app_path('Http/Middleware/RequirePermission.php'),
        );

        self::assertIsString($middleware);
        self::assertStringContainsString(
            'hasPermission',
            $middleware,
        );
        self::assertMatchesRegularExpression(
            '/abort(?:_unless)?\s*\([^;]*404/s',
            $middleware,
            'Permission denial must be concealed with an HTTP 404 response.',
        );
        self::assertStringNotContainsString(
            'abort(403',
            $middleware,
        );
    }
}
PHP

section "Reapplying the timestamp declaration repair"

python3 - <<'PY'
from pathlib import Path
import re

patterns = [
    re.compile(r"public const string UPDATED_AT\s*=\s*'';"),
    re.compile(r'public const string UPDATED_AT\s*=\s*"";'),
    re.compile(r"public const UPDATED_AT\s*=\s*'';"),
    re.compile(r'public const UPDATED_AT\s*=\s*"";'),
]

changed = []

for path in Path("app/Models").glob("*.php"):
    text = path.read_text()
    updated = text

    for pattern in patterns:
        updated = pattern.sub(
            "public const UPDATED_AT = null;",
            updated,
        )

    if updated != text:
        path.write_text(updated)
        changed.append(str(path))

if changed:
    print("Repaired:")
    print("\n".join(changed))
else:
    print("No stale empty-string UPDATED_AT declarations remained.")
PY

for model in \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php
do
    assert_file "${model}"
done

if grep -RInE \
    --include='*.php' \
    "public const( string)? UPDATED_AT[[:space:]]*=[[:space:]]*(''|\"\");" \
    app/Models; then
    echo "A broken empty-string UPDATED_AT declaration remains."
    exit 1
fi

section "Focused syntax and test verification"

php -l tests/Feature/Stabilization/PermissionConcealmentTest.php
php -l app/Http/Middleware/RequirePermission.php

vendor/bin/pint \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    app/Http/Middleware/RequirePermission.php \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php

vendor/bin/pint --test \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    app/Http/Middleware/RequirePermission.php \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php

vendor/bin/phpstan analyse \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    app/Http/Middleware/RequirePermission.php \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php \
    --memory-limit=1G \
    --error-format=table

php artisan test tests/Feature/Stabilization/PermissionConcealmentTest.php

section "Authorization, dashboard, and module guards"

assert_file app/Http/Controllers/Auth/AuthenticatedSessionController.php
assert_file app/Http/Controllers/Staff/StaffDashboardController.php
assert_file app/Services/Dashboard/StaffDashboardData.php
assert_file resources/views/staff/dashboard.blade.php
assert_file config/navigation.php
assert_file app/Services/Reports/StaffPerformanceReport.php

grep -q "dashboard.view" app/Http/Controllers/Auth/AuthenticatedSessionController.php
grep -q "admin.dashboard" app/Http/Controllers/Auth/AuthenticatedSessionController.php
grep -q "staff.dashboard" app/Http/Controllers/Auth/AuthenticatedSessionController.php
grep -q "redirect()->intended" app/Http/Controllers/Auth/AuthenticatedSessionController.php

if grep -q "Operational modules are introduced in later sprints" \
    resources/views/staff/dashboard.blade.php; then
    echo "The Sprint 3 staff-dashboard placeholder still exists."
    exit 1
fi

if grep -RIn "accounts.account_type" \
    app resources routes config database tests; then
    echo "The nonexistent accounts.account_type column is still referenced."
    exit 1
fi

python3 - <<'PY_GUARD'
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
    r"abort(?:_unless)?\s*\(.*?404",
    middleware,
    re.DOTALL,
):
    raise SystemExit(
        "RequirePermission does not conceal denial with HTTP 404.",
    )

if re.search(r"abort\s*\(\s*403", middleware):
    raise SystemExit(
        "RequirePermission still exposes HTTP 403.",
    )
PY_GUARD

php artisan route:list > /tmp/express-cloud-final-repair-routes.txt

for route in \
    "admin/dashboard" \
    "staff/dashboard" \
    "admin/inventory" \
    "admin/sales" \
    "admin/customers" \
    "admin/procurement" \
    "admin/accounting" \
    "admin/insights"
do
    if ! grep -Fq "${route}" /tmp/express-cloud-final-repair-routes.txt; then
        echo "Expected stabilized route missing: ${route}"
        exit 1
    fi
done

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

php artisan optimize:clear

find app config database routes tests \
    -type f -name '*.php' -print0 \
    | xargs -0 -n1 php -l \
    > /tmp/express-cloud-final-repair-php-lint.log

vendor/bin/pint
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G --error-format=table
php artisan test
composer validate --strict
composer audit
npm audit --audit-level=high
npm run build
git diff --check

section "Duplicate fragment guard"

python3 - <<'PY'
from pathlib import Path
import re

errors = []

for path in [
    *Path("app").rglob("*.php"),
    *Path("routes").rglob("*.php"),
    *Path("config").rglob("*.php"),
]:
    text = path.read_text()

    imports = re.findall(r"^use\s+([^;]+);$", text, re.MULTILINE)
    duplicate_imports = sorted(
        {item for item in imports if imports.count(item) > 1},
    )

    methods = re.findall(
        r"(?:public|protected|private)\s+function\s+(\w+)\s*\(",
        text,
    )
    duplicate_methods = sorted(
        {item for item in methods if methods.count(item) > 1},
    )

    if duplicate_imports:
        errors.append(
            f"{path}: duplicate imports: {duplicate_imports}",
        )

    if duplicate_methods:
        errors.append(
            f"{path}: duplicate methods: {duplicate_methods}",
        )

if errors:
    raise SystemExit("\n".join(errors))
PY

section "Committing final stabilization repair"

git add -- \
    app \
    config \
    database/migrations \
    resources/views \
    routes \
    tests \
    docs

if git diff --cached --quiet; then
    echo "No staged changes remain; fixes may already be present."
else
    git commit -m \
        "fix(stabilization): finalize authorization and runtime verification"
fi

if [[ "${SKIP_PUSH}" == "1" ]]; then
    echo "SKIP_PUSH=1; push skipped."
else
    git push -u origin "$(git branch --show-current)"
fi

section "Cleanup"

rm -f \
    express-cloud-stabilization-sprint.sh \
    express-cloud-stabilization-repair.sh \
    express-cloud-stabilization-final-repair.sh \
    engineering-audit-report.txt
rm -rf .sprint-logs

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "Tracked files remain modified after final stabilization."
    git status --short
    exit 1
fi

rm -f -- "${SCRIPT_PATH}"

echo
echo "============================================================"
echo "EXPRESS CLOUD STABILIZATION COMPLETED"
echo "============================================================"
echo
echo "No SQL dump was generated."
echo "No ZIP was packaged."
echo "The application version was not changed."
echo "Log: ${LOG_FILE}"
git log --oneline -6
