#!/usr/bin/env bash

set -uo pipefail

PASS=0
FAIL=0

pass() {
    printf 'PASS: %s\n' "$1"
    PASS=$((PASS + 1))
}

fail() {
    printf 'FAIL: %s\n' "$1"
    FAIL=$((FAIL + 1))
}

section() {
    printf '\n===== %s =====\n' "$1"
}

check_file() {
    local file="$1"

    if [[ -f "$file" ]]; then
        pass "$file exists"
    else
        fail "$file is missing"
    fi
}

check_contains() {
    local file="$1"
    local pattern="$2"
    local description="$3"

    if [[ ! -f "$file" ]]; then
        fail "$description: file missing — $file"
        return
    fi

    if grep -Fq -- "$pattern" "$file"; then
        pass "$description"
    else
        fail "$description"
    fi
}

lint_php() {
    local file="$1"

    if [[ ! -f "$file" ]]; then
        fail "Cannot lint missing file: $file"
        return
    fi

    if php -l "$file" >/dev/null 2>&1; then
        pass "PHP syntax: $file"
    else
        fail "PHP syntax: $file"
        php -l "$file" || true
    fi
}

section "Required files"

check_file app/Http/Controllers/Admin/Catalog/ProductController.php
check_file app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php
check_file app/Http/Controllers/Admin/Sales/SaleController.php
check_file app/Http/Requests/Admin/Catalog/UpdateProductRequest.php
check_file app/Queries/Activity/SystemActivityQuery.php
check_file app/Services/Reports/StaffPerformanceReport.php
check_file database/seeders/Sprint4EnterprisePermissionSeeder.php
check_file resources/views/admin/catalog/products/edit.blade.php
check_file resources/views/admin/catalog/products/index.blade.php
check_file resources/views/admin/activity/index.blade.php
check_file resources/views/admin/branches/index.blade.php
check_file resources/views/admin/imports/products/index.blade.php
check_file resources/views/admin/sales/partials/quick-customer-modal.blade.php
check_file resources/views/components/layout/app-shell.blade.php
check_file resources/views/components/navigation/topbar.blade.php

section "PHP syntax"

lint_php app/Http/Controllers/Admin/Catalog/ProductController.php
lint_php app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php
lint_php app/Http/Controllers/Admin/Sales/SaleController.php
lint_php app/Http/Requests/Admin/Catalog/UpdateProductRequest.php
lint_php app/Queries/Activity/SystemActivityQuery.php
lint_php app/Services/Reports/StaffPerformanceReport.php
lint_php database/seeders/Sprint4EnterprisePermissionSeeder.php
lint_php routes/admin.php

section "Product editing"

check_contains routes/admin.php "products.edit" "Product edit route exists"
check_contains routes/admin.php "products.update" "Product update route exists"
check_contains app/Http/Controllers/Admin/Catalog/ProductController.php "function edit(" "Product edit controller action exists"
check_contains app/Http/Controllers/Admin/Catalog/ProductController.php "function update(" "Product update controller action exists"
check_contains resources/views/admin/catalog/products/index.blade.php "admin.catalog.products.edit" "Product table contains an Edit action"
check_contains resources/views/admin/catalog/products/edit.blade.php "Save changes" "Product edit form contains save action"

section "Bulk price adjustment"

check_contains routes/admin.php "price-adjustments" "Bulk price adjustment route exists"
check_contains app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php "function index(" "Bulk price adjustment page action exists"
check_contains app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php "function store(" "Bulk price adjustment submission action exists"
check_contains app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php "ProductBranchPrice::query()" "Bulk adjustment updates canonical branch prices"
check_contains config/navigation.php "Bulk Price Update" "Bulk price adjustment navigation item exists"
check_contains database/seeders/Sprint4EnterprisePermissionSeeder.php "products.prices.adjust" "Bulk pricing permission is seeded"

section "Lisa AI"

check_contains config/navigation.php "Lisa AI" "Lisa AI navigation item exists"
check_contains config/navigation.php "insights.chat.index" "Lisa AI navigation route exists"
check_contains routes/admin.php "insights.chat.index" "Lisa AI named route exists"
check_contains database/seeders/Sprint4EnterprisePermissionSeeder.php "lisa.chat" "Lisa chat permission is seeded"
check_contains database/seeders/Sprint4EnterprisePermissionSeeder.php "lisa.audit.view" "Lisa audit permission is seeded"

section "Sales and payment methods"

check_contains app/Http/Controllers/Admin/Sales/SaleController.php "PaymentMethod::query()" "Sales controller queries payment methods"
check_contains app/Http/Controllers/Admin/Sales/SaleController.php "ProductBranchPrice::query()" "Sales controller reads canonical branch prices"
check_contains app/Http/Controllers/Admin/Sales/SaleController.php "'paymentMethods'" "Payment methods are passed to the sales view"
check_contains app/Http/Controllers/Admin/Sales/SaleController.php "'products'" "Products are passed to the sales view"

section "Activity log"

check_contains app/Queries/Activity/SystemActivityQuery.php "actor_account_id" "Activity query uses actor_account_id"
check_contains app/Queries/Activity/SystemActivityQuery.php "actor_first_name" "Activity query loads actor first name"
check_contains app/Queries/Activity/SystemActivityQuery.php "actor_last_name" "Activity query loads actor last name"
check_contains app/Queries/Activity/SystemActivityQuery.php "actor_branch_name" "Activity query loads actor branch name"
check_contains resources/views/admin/activity/index.blade.php "actorName.' ['" "Activity view formats name with branch"
check_contains resources/views/admin/activity/index.blade.php ": \$actorName;" "Activity view omits brackets when branch is absent"

section "Customer modal"

check_contains resources/views/admin/sales/partials/quick-customer-modal.blade.php "max-h-[min(82vh,720px)]" "Customer modal height is limited"
check_contains resources/views/admin/sales/partials/quick-customer-modal.blade.php "overflow-y-auto" "Customer modal has nested scrolling"
check_contains resources/views/admin/sales/partials/quick-customer-modal.blade.php "Close customer form" "Customer modal has a close button"
check_contains resources/views/admin/sales/partials/quick-customer-modal.blade.php "bg-blue-600" "New Customer control is styled as a primary button"

section "Fixed topbar"

check_contains resources/views/components/navigation/topbar.blade.php "fixed left-0 right-0 top-0" "Topbar is fixed"
check_contains resources/views/components/layout/app-shell.blade.php "pt-20" "Page content is offset below the fixed topbar"

section "Responsive width fixes"

check_contains resources/views/admin/branches/index.blade.php "min-w-0" "Branches page contains width constraints"
check_contains resources/views/admin/branches/index.blade.php "max-w-full" "Branches page is constrained to viewport width"
check_contains resources/views/admin/imports/products/index.blade.php "min-w-0" "Product import history contains width constraints"
check_contains resources/views/admin/imports/products/index.blade.php "overflow-hidden" "Product import history prevents viewport overflow"

section "Previously deployed live fixes"

check_contains app/Services/Reports/StaffPerformanceReport.php "GREATEST(grand_total_kobo - paid_amount_kobo, 0)" "Outstanding balance uses calculated sale balance"
check_contains app/Http/Controllers/Admin/Sales/SaleController.php "use App\Models\Product;" "Sale controller imports Product"
check_contains app/Queries/Activity/SystemActivityQuery.php "actor_account_id" "Activity query retains actor column fix"

section "Compiled frontend"

if [[ -f public/build/manifest.json ]]; then
    pass "Vite manifest exists"
else
    fail "Vite manifest is missing; run npm run build"
fi

if [[ -d public/build/assets ]] && find public/build/assets -maxdepth 1 -type f | grep -q .; then
    pass "Compiled frontend assets exist"
else
    fail "Compiled frontend assets are missing"
fi

section "Git state"

CURRENT_COMMIT="$(git rev-parse --short HEAD 2>/dev/null || true)"
CURRENT_BRANCH="$(git branch --show-current 2>/dev/null || true)"

if [[ -n "$CURRENT_COMMIT" ]]; then
    pass "Current commit: $CURRENT_COMMIT"
else
    fail "Could not read current Git commit"
fi

if [[ -n "$CURRENT_BRANCH" ]]; then
    pass "Current branch: $CURRENT_BRANCH"
else
    fail "Could not read current Git branch"
fi

if git status --porcelain | grep -q .; then
    printf 'NOTICE: Workspace contains uncommitted files:\n'
    git status --short
else
    pass "Working tree is clean"
fi

printf '\n========================================\n'
printf 'Verification results\n'
printf '========================================\n'
printf 'Passed: %d\n' "$PASS"
printf 'Failed: %d\n' "$FAIL"

if [[ "$FAIL" -gt 0 ]]; then
    printf '\nSTATIC VERIFICATION FAILED\n'
    exit 1
fi

printf '\nSTATIC VERIFICATION PASSED\n'
printf 'No database connection was used.\n'
