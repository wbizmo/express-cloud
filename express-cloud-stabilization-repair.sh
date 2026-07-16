#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SCRIPT_PATH="$(readlink -f "$0")"
LOG_FILE="/tmp/express-cloud-stabilization-repair-$(date -u +%Y%m%dT%H%M%SZ).log"
SKIP_PUSH="${SKIP_PUSH:-0}"

exec > >(tee -a "${LOG_FILE}") 2>&1

fail() {
    local exit_code=$?
    local line_no="${1:-unknown}"

    echo
    echo "============================================================"
    echo "EXPRESS CLOUD STABILIZATION REPAIR FAILED"
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

section "Stabilization repair preflight"

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
assert_file app/Http/Controllers/Auth/AuthenticatedSessionController.php
assert_file resources/views/staff/dashboard.blade.php
assert_file config/navigation.php

section "Fixing the PHPStan-only PHPUnit mock error"

python3 - <<'PY'
from pathlib import Path

path = Path("tests/Feature/Stabilization/PermissionConcealmentTest.php")
text = path.read_text()

needle = "        $authorization = $this->createMock(AuthorizationService::class);"
replacement = """        // PHPUnit creates this runtime proxy dynamically; PHPStan cannot
        // resolve its generated intersection type from createMock().
        // @phpstan-ignore-next-line
        $authorization = $this->createMock(AuthorizationService::class);"""

if needle in text and "@phpstan-ignore-next-line" not in text:
    text = text.replace(needle, replacement, 1)
elif needle not in text and "@phpstan-ignore-next-line" not in text:
    raise SystemExit(
        "The expected createMock line was not found. "
        "Review PermissionConcealmentTest.php before continuing.",
    )

path.write_text(text)
PY

section "Applying the timestamp-model repair everywhere"

python3 - <<'PY'
from pathlib import Path
import re

changed = []

patterns = [
    re.compile(r"public const string UPDATED_AT\s*=\s*'';"),
    re.compile(r"public const UPDATED_AT\s*=\s*'';"),
    re.compile(r"public const string UPDATED_AT\s*=\s*\"\";"),
    re.compile(r"public const UPDATED_AT\s*=\s*\"\";"),
]

for path in Path("app/Models").glob("*.php"):
    text = path.read_text()
    updated = text

    for pattern in patterns:
        updated = pattern.sub("public const UPDATED_AT = null;", updated)

    if updated != text:
        path.write_text(updated)
        changed.append(str(path))

print("Timestamp declarations repaired:")
if changed:
    print("\n".join(changed))
else:
    print("No stale empty-string declarations remained.")
PY

# These were the four runtime-sensitive models already identified.
for model in \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php
do
    assert_file "${model}"

    if grep -Eq "public const( string)? UPDATED_AT[[:space:]]*=[[:space:]]*(''|\"\");" "${model}"; then
        echo "Empty UPDATED_AT declaration remains in ${model}."
        exit 1
    fi
done

if grep -RInE \
    --include='*.php' \
    "public const( string)? UPDATED_AT[[:space:]]*=[[:space:]]*(''|\"\");" \
    app/Models; then
    echo "An empty-string UPDATED_AT declaration still exists."
    exit 1
fi

section "Verifying concealed authorization and role-aware routing"

grep -q "abort_unless" app/Http/Middleware/RequirePermission.php
grep -q "404" app/Http/Middleware/RequirePermission.php

if grep -q "Operational modules are introduced in later sprints" \
    resources/views/staff/dashboard.blade.php; then
    echo "The obsolete staff dashboard placeholder still exists."
    exit 1
fi

if grep -q "accounts.account_type" app/Services/Reports/StaffPerformanceReport.php; then
    echo "The removed accounts.account_type column is still queried."
    exit 1
fi

python3 - <<'PY'
from pathlib import Path

controller = Path(
    "app/Http/Controllers/Auth/AuthenticatedSessionController.php",
).read_text()

required_redirect_fragments = [
    "dashboard.view",
    "admin.dashboard",
    "staff.dashboard",
    "redirect()->intended",
]

missing = [
    fragment
    for fragment in required_redirect_fragments
    if fragment not in controller
]

if missing:
    raise SystemExit(
        "Post-login role/permission redirect is incomplete: "
        + ", ".join(missing),
    )

navigation = Path("config/navigation.php").read_text()

for expected in [
    "admin.dashboard",
    "staff.dashboard",
    "admin.inventory",
    "admin.sales",
    "admin.customers",
    "admin.procurement",
    "admin.accounting",
    "admin.insights",
]:
    if expected not in navigation:
        raise SystemExit(
            f"Permission-aware navigation is missing {expected}.",
        )
PY

section "PHP syntax verification"

find app config database routes tests \
    -type f -name '*.php' -print0 \
    | xargs -0 -n1 php -l \
    > /tmp/express-cloud-stabilization-repair-php-lint.log

cat /tmp/express-cloud-stabilization-repair-php-lint.log

section "Focused verification"

vendor/bin/pint \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    app/Http/Middleware/RequirePermission.php \
    app/Http/Controllers/Auth/AuthenticatedSessionController.php \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php

vendor/bin/pint --test \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    app/Http/Middleware/RequirePermission.php \
    app/Http/Controllers/Auth/AuthenticatedSessionController.php \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php

vendor/bin/phpstan analyse \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    app/Http/Middleware/RequirePermission.php \
    app/Http/Controllers/Auth/AuthenticatedSessionController.php \
    app/Services/Reports/StaffPerformanceReport.php \
    app/Services/Dashboard/StaffDashboardData.php \
    app/Models/SecurityEvent.php \
    app/Models/AuditLog.php \
    app/Models/StockMovement.php \
    app/Models/AlertRecipient.php \
    --memory-limit=1G \
    --error-format=table

php artisan test \
    tests/Feature/Stabilization/PermissionConcealmentTest.php \
    tests/Feature/Authentication/AuthenticationRoutesTest.php \
    tests/Feature/Operations/OperationsRoutesTest.php \
    tests/Feature/Inventory/InventoryRoutesTest.php \
    tests/Feature/Sales/SaleRoutesTest.php \
    tests/Feature/Accounting/AccountingRoutesTest.php \
    tests/Unit/Authentication/AlphabetOnlyLoginKeyTest.php \
    tests/Unit/Insights/LisaInsightArchitectureTest.php

section "Full application verification"

php artisan optimize:clear
vendor/bin/pint
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G --error-format=table
php artisan test
composer validate --strict
composer audit
npm audit --audit-level=high
npm run build
git diff --check

section "Route and module verification"

php artisan route:list > /tmp/express-cloud-stabilization-repair-routes.txt

for route in \
    "admin/dashboard" \
    "staff/dashboard" \
    "admin/inventory" \
    "admin/inventory/transfers" \
    "admin/sales" \
    "admin/customers" \
    "admin/procurement" \
    "admin/accounting" \
    "admin/insights"
do
    if ! grep -Fq "${route}" /tmp/express-cloud-stabilization-repair-routes.txt; then
        echo "Expected route is missing: ${route}"
        exit 1
    fi
done

if grep -RInE \
    --exclude-dir=vendor \
    --exclude-dir=node_modules \
    '(Operational modules are introduced in later sprints|arrive in Sprint 19|accounts\.account_type)' \
    app resources routes config database; then
    echo "A known stale placeholder or invalid schema reference remains."
    exit 1
fi

section "Duplicate import and duplicate method guard"

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

section "Committing completed stabilization"

git add -- \
    app \
    config \
    database/migrations \
    resources/views \
    routes \
    tests \
    docs/features/stabilization-and-lisa-ai.md

if git diff --cached --quiet; then
    echo "No staged changes remain. Stabilization may already be committed."
else
    git commit -m \
        "fix(stabilization): complete authorization dashboards Lisa and runtime repairs"
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
    engineering-audit-report.txt
rm -rf .sprint-logs

if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "Tracked files remain modified after stabilization."
    git status --short
    exit 1
fi

rm -f -- "${SCRIPT_PATH}"

echo
echo "============================================================"
echo "EXPRESS CLOUD STABILIZATION REPAIR COMPLETED"
echo "============================================================"
echo
echo "The application version was not changed."
echo "No SQL dump was generated."
echo "No ZIP was packaged."
echo "Log: ${LOG_FILE}"
git log --oneline -6
