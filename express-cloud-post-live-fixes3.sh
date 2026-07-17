#!/usr/bin/env bash
set -Eeuo pipefail

# Portable repository search; no ripgrep dependency.
search_php() {
  local pattern="$1"
  shift
  grep -RInE --include='*.php' "$pattern" "$@"
}

APP_DIR="${1:-$(pwd)}"
cd "$APP_DIR"

required=(artisan package.json app bootstrap config database public resources routes)
for path in "${required[@]}"; do
  [[ -e "$path" ]] || { echo "[error] Run this from the Express Cloud repository root (missing: $path)" >&2; exit 1; }
done

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="storage/app/patch-backups/post-live-strengthened-$STAMP"
RELEASE_DIR="release"
mkdir -p "$BACKUP_DIR" "$RELEASE_DIR"

backup() {
  local file="$1"
  [[ -e "$file" ]] || return 0
  mkdir -p "$BACKUP_DIR/$(dirname "$file")"
  cp -a "$file" "$BACKUP_DIR/$file"
}

backup_many() {
  for file in "$@"; do backup "$file"; done
}

backup_many \
  app/Services/Dashboard/StaffDashboardData.php \
  app/Services/Insights/LisaInsightEngine.php \
  app/Services/Organisation/AuthorizationService.php \
  app/Support/Authorization/PermissionCatalog.php \
  app/Http/Middleware/RequirePermission.php \
  app/Http/Middleware/EnsureSaleVisibility.php \
  app/Http/Controllers/Admin/RoleController.php \
  app/Http/Controllers/Admin/Sales/SaleController.php \
  app/Http/Controllers/Admin/Inventory/InventoryController.php \
  app/Actions/Sales/CreateSale.php \
  resources/views/staff/dashboard.blade.php \
  resources/views/admin/sales/create.blade.php \
  resources/views/admin/inventory/index.blade.php \
  resources/views/components/layout/app-shell.blade.php \
  resources/views/components/navigation/topbar.blade.php \
  resources/css/app.css \
  resources/js/app.js \
  routes/web.php routes/admin.php routes/staff.php routes/api.php \
  bootstrap/app.php public/index.php public/.htaccess public/robots.txt index.php .htaccess robots.txt vite.config.js

echo "[1/12] Correcting every known live-schema mismatch"
python3 <<'PY'
from pathlib import Path

replacements = {
    'app/Services/Dashboard/StaffDashboardData.php': {
        "'reference', 'sale_type', 'status', 'sale_date', 'grand_total_kobo', 'balance_due_kobo'": "'sale_code', 'sale_type', 'status', 'sale_date', 'grand_total_kobo', 'paid_amount_kobo'",
        "DB::table('product_branch_stocks')": "DB::table('product_branch_stock')",
        "'reorder_level_milliunits'": "'minimum_stock_milliunits'",
    },
    'app/Services/Insights/LisaInsightEngine.php': {
        "DB::table('product_branch_stocks')": "DB::table('product_branch_stock')",
        "'reorder_level_milliunits'": "'minimum_stock_milliunits'",
    },
    'resources/views/staff/dashboard.blade.php': {
        '{{ $sale->reference }}': '{{ $sale->sale_code }}',
    },
}
for filename, mapping in replacements.items():
    p = Path(filename)
    if not p.exists():
        continue
    s = p.read_text()
    for old, new in mapping.items():
        s = s.replace(old, new)
    p.write_text(s)
PY

cat > app/Services/Dashboard/StaffDashboardData.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Account;
use App\Services\Organisation\AuthorizationService;
use Illuminate\Support\Facades\DB;

final readonly class StaffDashboardData
{
    public function __construct(private AuthorizationService $authorization) {}

    /** @return array<string, mixed> */
    public function for(Account $account): array
    {
        $sales = DB::table('sales')
            ->where('sold_by_account_id', $account->getKey())
            ->whereNotIn('status', ['cancelled']);

        $today = today()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $recentSales = (clone $sales)
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get([
                'id', 'sale_code', 'sale_type', 'status', 'sale_date',
                'grand_total_kobo', 'paid_amount_kobo',
            ])
            ->map(function (object $sale): object {
                $sale->balance_due_kobo = max(
                    0,
                    (int) $sale->grand_total_kobo - (int) $sale->paid_amount_kobo,
                );

                return $sale;
            });

        $outstandingKobo = (int) (clone $sales)
            ->selectRaw('COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS outstanding_total')
            ->value('outstanding_total');

        return [
            'todaySalesCount' => (clone $sales)->whereDate('sale_date', $today)->count(),
            'todayRevenueKobo' => (int) (clone $sales)->whereDate('sale_date', $today)->sum('grand_total_kobo'),
            'monthRevenueKobo' => (int) (clone $sales)->whereBetween('sale_date', [$monthStart, $today])->sum('grand_total_kobo'),
            'outstandingKobo' => $outstandingKobo,
            'recentSales' => $recentSales,
            'permissions' => $this->authorization->permissionSlugs($account),
        ];
    }
}
PHP

python3 <<'PY'
from pathlib import Path
p = Path('app/Services/Insights/LisaInsightEngine.php')
if p.exists():
    s = p.read_text()
    s = s.replace("->where('balance_due_kobo', '>', 0)\n            ->whereNotIn('status', ['cancelled'])\n            ->sum('balance_due_kobo')", "->whereNotIn('status', ['cancelled'])\n            ->selectRaw('COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS outstanding_total')\n            ->value('outstanding_total')")
    p.write_text(s)
PY

# Fail loudly if the known invalid identifiers remain in executable application code.
if search_php "product_branch_stocks|reorder_level_milliunits" app routes resources database/migrations; then
  echo "[error] A legacy stock-table identifier still exists. Review the lines above." >&2
  exit 1
fi
if search_php "sales.*balance_due_kobo|where\('balance_due_kobo'|sum\('balance_due_kobo'" app resources; then
  echo "[error] A legacy persisted sales balance reference still exists. Review the lines above." >&2
  exit 1
fi

echo "[2/12] Installing explicit role families and least-privilege matrices"
mkdir -p app/Support/Authorization
cat > app/Support/Authorization/RolePermissionPolicy.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Authorization;

final class RolePermissionPolicy
{
    /** @var array<string, list<string>> */
    private const MATRICES = [
        'sales' => [
            'dashboard.staff.view', 'sales.view.own', 'sales.create',
            'sales.payments.record', 'sales.returns.create',
            'vouchers.apply', 'quotes.convert',
            'customers.view.assigned', 'customers.create', 'customers.update.assigned',
            'documents.sales.print', 'catalog.sale-search',
        ],
        'inventory' => [
            'dashboard.inventory.view', 'products.view', 'products.create', 'products.update',
            'products.deactivate', 'categories.manage', 'brands.manage', 'tax-rates.manage',
            'inventory.view', 'inventory.movements.view', 'inventory.intake',
            'inventory.transfer', 'inventory.adjust', 'reports.low-stock',
            'products.prices.adjust', 'products.zero-stock.manage',
            'documents.products.labels', 'catalog.inventory-search',
        ],
        'procurement' => [
            'dashboard.procurement.view', 'products.view', 'suppliers.view',
            'suppliers.create', 'suppliers.update', 'procurement.view',
            'procurement.create', 'procurement.receive', 'purchases.record',
            'supplier-bills.view', 'supplier-bills.create',
            'supplier-documents.download', 'catalog.procurement-search',
        ],
        'accounting' => [
            'dashboard.accounting.view', 'customers.receivables.view',
            'supplier-bills.view', 'supplier-bills.pay', 'reports.supplier-balances',
            'receipts.view', 'receipts.create', 'purchase_returns.view',
            'purchase_returns.create', 'assets.view', 'assets.manage',
            'operation_documents.download', 'documents.branding.manage',
            'accounting.accounts.view', 'accounting.accounts.manage',
            'accounting.journals.view', 'accounting.journals.create',
            'accounting.journals.reverse', 'accounting.periods.manage',
            'accounting.reports.view', 'accounting.sync', 'accounting.depreciation.post',
            'reports.hub.view', 'reports.export',
        ],
        'auditor' => [
            'dashboard.audit.view', 'company.view', 'branches.view', 'staff.view',
            'roles.view', 'products.view', 'suppliers.view', 'inventory.view',
            'inventory.movements.view', 'procurement.view', 'sales.view.all',
            'supplier-bills.view', 'supplier-returns.view', 'reports.hub.view',
            'accounting.accounts.view', 'accounting.journals.view',
            'accounting.reports.view', 'activity.view', 'activity.products.view',
            'security-events.view', 'audit-log.view', 'audit-log.export',
        ],
        'branch-manager' => [
            'dashboard.view', 'branches.view', 'staff.view', 'products.view',
            'inventory.view', 'inventory.movements.view', 'inventory.intake',
            'inventory.transfer', 'inventory.adjust', 'reports.low-stock',
            'sales.view.all', 'sales.create', 'sales.payments.record',
            'sales.returns.create', 'customers.view', 'customers.create',
            'customers.update', 'procurement.view', 'procurement.create',
            'procurement.approve', 'procurement.receive', 'reports.staff-performance',
            'documents.sales.print', 'catalog.sale-search', 'catalog.inventory-search',
        ],
    ];

    /** @param list<string> $requested @return list<string> */
    public static function constrain(string $name, string $slug, array $requested): array
    {
        $family = self::family($name, $slug);
        $requested = array_values(array_unique(array_filter($requested, 'is_string')));

        if ($family === null || in_array($family, ['system-owner', 'administrator'], true)) {
            return $requested;
        }

        return array_values(array_intersect($requested, self::MATRICES[$family] ?? []));
    }

    public static function family(string $name, string $slug): ?string
    {
        $identity = mb_strtolower(trim($name.' '.$slug));
        $patterns = [
            'system-owner' => '/\b(system[ _-]?owner|owner)\b/u',
            'administrator' => '/\b(admin|administrator)\b/u',
            'branch-manager' => '/\b(branch[ _-]?manager|store[ _-]?manager)\b/u',
            'sales' => '/\b(sales|cashier|salesperson|sales[ _-]?rep)\b/u',
            'inventory' => '/\b(inventory|stock|warehouse|storekeeper)\b/u',
            'procurement' => '/\b(procurement|purchasing|buyer)\b/u',
            'accounting' => '/\b(accounting|accountant|finance|bookkeeper)\b/u',
            'auditor' => '/\b(auditor|audit|compliance)\b/u',
        ];
        foreach ($patterns as $family => $pattern) {
            if (preg_match($pattern, $identity) === 1) return $family;
        }
        return null;
    }
}
PHP

python3 <<'PY'
from pathlib import Path
p=Path('app/Support/Authorization/PermissionCatalog.php')
s=p.read_text()
# Remove API token permission completely.
s=s.replace("                'api.tokens.manage' => 'Manage API tokens',\n", '')
# Add missing explicit capabilities near catalog group.
needle="                'products.update' => 'Update products',\n"
extra=("                'products.prices.adjust' => 'Bulk-adjust branch product prices',\n"
       "                'products.zero-stock.manage' => 'Manage zero-stock sale policy',\n"
       "                'catalog.sale-search' => 'Search sale catalogue with branch availability',\n"
       "                'catalog.inventory-search' => 'Search inventory catalogue',\n"
       "                'catalog.procurement-search' => 'Search procurement catalogue',\n")
if extra not in s:
    s=s.replace(needle, needle+extra)
# Add role/dashboard/customer-scoping permissions.
s=s.replace("                'dashboard.view' => 'View admin dashboard',\n", "                'dashboard.view' => 'View admin dashboard',\n                'dashboard.staff.view' => 'View sales staff dashboard',\n                'dashboard.inventory.view' => 'View inventory dashboard',\n                'dashboard.procurement.view' => 'View procurement dashboard',\n                'dashboard.accounting.view' => 'View accounting dashboard',\n                'dashboard.audit.view' => 'View audit dashboard',\n")
s=s.replace("                'customers.view' => 'View customers',\n", "                'customers.view' => 'View all customers',\n                'customers.view.assigned' => 'View customers linked to own sales',\n")
s=s.replace("                'customers.update' => 'Update customers',\n", "                'customers.update' => 'Update all customers',\n                'customers.update.assigned' => 'Update customers linked to own sales',\n")
p.write_text(s)
PY

# Role writes are constrained by role family. Replace the controller with a known-good,
# idempotent implementation instead of modifying imports or expressions in place.
cat > app/Http/Controllers/Admin/RoleController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Organisation\AuditLogger;
use App\Support\Authorization\PermissionCatalog;
use App\Support\Authorization\RolePermissionPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class RoleController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()
                ->withCount(['accounts', 'permissions'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->paginate(25),
            'permissionGroups' => PermissionCatalog::grouped(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $slug = $request->string('slug')->toString();
        $permissions = RolePermissionPolicy::constrain(
            $name,
            $slug,
            $request->array('permissions'),
        );

        $role = DB::transaction(function () use ($request, $name, $slug, $permissions): Role {
            $role = Role::query()->create([
                'name' => $name,
                'slug' => $slug,
                'description' => $request->string('description')->toString() ?: null,
                'is_system' => false,
                'is_active' => true,
            ]);

            $permissionIds = Permission::query()
                ->whereIn('slug', $permissions)
                ->pluck('id');

            $role->permissions()->sync($permissionIds);

            return $role;
        });

        $this->audit->record(
            $request,
            'role.created',
            'role',
            $role,
            after: [
                'name' => $role->name,
                'slug' => $role->slug,
                'permissions' => $permissions,
            ],
        );

        return back()->with('status', 'Role created.');
    }
}
PHP

# Authorization remains permission-driven: no role-name shortcut and no permission creep.
cat > app/Services/Organisation/AuthorizationService.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use Illuminate\Support\Collection;

final class AuthorizationService
{
    /** @var array<string, array<string, bool>> */
    private array $permissionCache = [];

    public function hasPermission(Account $account, string $permission): bool
    {
        $accountId = (string) $account->getKey();
        if (array_key_exists($permission, $this->permissionCache[$accountId] ?? [])) {
            return $this->permissionCache[$accountId][$permission];
        }

        $allowed = $account->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', static fn ($query) => $query->where('permissions.slug', $permission))
            ->exists();

        return $this->permissionCache[$accountId][$permission] = $allowed;
    }

    /** @param list<string> $permissions */
    public function hasAnyPermission(Account $account, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($account, $permission)) return true;
        }
        return false;
    }

    /** @return Collection<int, string> */
    public function permissionSlugs(Account $account): Collection
    {
        return $account->roles()
            ->where('roles.is_active', true)
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(static fn ($role) => $role->permissions->pluck('slug'))
            ->unique()->values();
    }
}
PHP

echo "[3/12] Enforcing route, record and branch authorization as concealed 404s"
python3 <<'PY'
from pathlib import Path
p=Path('app/Http/Middleware/EnsureSaleVisibility.php')
if p.exists():
    p.write_text(p.read_text().replace('            403,', '            404,'))
PY

mkdir -p app/Services/Organisation
cat > app/Services/Organisation/BranchAccess.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

final class BranchAccess
{
    public function canAccess(Account $account, Branch|string $branch): bool
    {
        $branchId = $branch instanceof Branch ? (string) $branch->getKey() : $branch;
        return $account->is_allowed_all_branches
            || $account->branches()->whereKey($branchId)->exists();
    }

    public function enforce(Account $account, Branch|string $branch): void
    {
        abort_unless($this->canAccess($account, $branch), 404);
    }

    public function scope(Account $account, Builder $query, string $column = 'branch_id'): Builder
    {
        if ($account->is_allowed_all_branches) return $query;
        return $query->whereIn($column, $account->branches()->select('branches.id'));
    }
}
PHP

# Dashboard itself also requires its intended permission.
python3 <<'PY'
from pathlib import Path
p=Path('routes/staff.php')
s=p.read_text()
s=s.replace("Route::get('/dashboard', StaffDashboardController::class)\n        ->name('dashboard');", "Route::get('/dashboard', StaffDashboardController::class)\n        ->middleware('permission:dashboard.staff.view')\n        ->name('dashboard');")
p.write_text(s)
PY

echo "[4/12] Removing the imported API/token surface"
cat > routes/api.php <<'PHP'
<?php

declare(strict_types=1);

// Express Cloud is a server-rendered application. Public API routes are intentionally disabled.
PHP
python3 <<'PY'
from pathlib import Path
p=Path('bootstrap/app.php')
s=p.read_text()
s=s.replace('use App\\Http\\Middleware\\AuthenticateApiToken;\n', '')
s=s.replace("        api: __DIR__.'/../routes/api.php',\n", '')
s=s.replace("            'api.token' => AuthenticateApiToken::class,\n", '')
s=s.replace("        $exceptions->shouldRenderJsonWhen(\n            fn (Request $request) => $request->is('api/*'),\n        );\n", '')
p.write_text(s)

p=Path('routes/admin.php')
s=p.read_text()
s=s.replace('use App\\Http\\Controllers\\Admin\\Api\\ApiTokenController;\n', '')
import re
s=re.sub(r"\n\s*Route::prefix\('api[^;]+?\n\s*\}\);", "", s, flags=re.S)
s=re.sub(r"\n\s*Route::resource\(['\"]api[^;]+;", "", s)
p.write_text(s)

# Remove API navigation fragments where present.
for p in Path('resources/views').rglob('*.blade.php'):
    s=p.read_text()
    s=re.sub(r"\s*@can\('api\.tokens\.manage'\).*?@endcan", "", s, flags=re.S)
    s=re.sub(r"\s*<[^>]+href=\"\{\{\s*route\('admin\.api[^\"]+.*?</[^>]+>", "", s, flags=re.S)
    p.write_text(s)
PY

echo "[5/12] Adding universal branch-aware product lookup and barcode scanning"
mkdir -p app/Http/Controllers/Catalog app/Services/Catalog resources/views/components/catalog
cat > app/Services/Catalog/ProductLookup.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Services\Organisation\BranchAccess;
use Illuminate\Support\Collection;

final readonly class ProductLookup
{
    public function __construct(private BranchAccess $branches) {}

    /** @return Collection<int, array<string, mixed>> */
    public function search(Account $actor, Branch $branch, string $term, int $limit = 20): Collection
    {
        $this->branches->enforce($actor, $branch);
        $term = trim($term);
        abort_if($term === '', 422);

        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($term): void {
                $query->where('barcode', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('name', 'like', '%'.addcslashes($term, '%_\\').'%');
            })
            ->with([
                'branchStock' => fn ($query) => $query->where('branch_id', $branch->getKey()),
                'branchPrices' => fn ($query) => $query->where('branch_id', $branch->getKey()),
            ])
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 WHEN sku = ? THEN 1 ELSE 2 END', [$term, $term])
            ->orderBy('name')
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(function (Product $product) use ($branch): array {
                $stock = $product->branchStock->first();
                $price = $product->branchPrices->first();
                return [
                    'id' => (string) $product->getKey(),
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'track_inventory' => (bool) $product->track_inventory,
                    'quantity_milliunits' => (int) ($stock?->quantity_milliunits ?? 0),
                    'quantity' => number_format(((int) ($stock?->quantity_milliunits ?? 0)) / 1000, 3, '.', ''),
                    'price_kobo' => (int) ($price?->price_kobo ?? $product->default_price_kobo),
                    'price' => number_format(((int) ($price?->price_kobo ?? $product->default_price_kobo)) / 100, 2, '.', ''),
                    'branch_id' => (string) $branch->getKey(),
                ];
            });
    }
}
PHP

cat > app/Http/Controllers/Catalog/ProductLookupController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Models\Account;
use App\Models\Branch;
use App\Services\Catalog\ProductLookup;
use App\Services\Organisation\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ProductLookupController
{
    public function __construct(
        private ProductLookup $lookup,
        private AuthorizationService $authorization,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $context = $request->string('context')->toString();
        $permission = match ($context) {
            'sale' => 'catalog.sale-search',
            'inventory' => 'catalog.inventory-search',
            'procurement' => 'catalog.procurement-search',
            default => null,
        };
        abort_unless($permission !== null && $this->authorization->hasPermission($actor, $permission), 404);

        $branch = Branch::query()->findOrFail($request->string('branch_id')->toString());
        return response()->json([
            'data' => $this->lookup->search($actor, $branch, $request->string('q')->toString())->all(),
        ]);
    }
}
PHP

cat > resources/views/components/catalog/product-scanner.blade.php <<'BLADE'
@props([
    'name' => 'product_id',
    'branchField' => 'branch_id',
    'context' => 'sale',
    'label' => 'Scan barcode or search product',
    'required' => true,
    'showAvailability' => true,
])
<div
    class="ec-product-scanner min-w-0"
    x-data="productScanner({
        endpoint: @js(route('catalog.products.lookup')),
        context: @js($context),
        branchField: @js($branchField),
    })"
    x-on:keydown.escape.window="close()"
>
    <label class="block min-w-0">
        <span class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</span>
        <input type="hidden" name="{{ $name }}" x-model="selectedId" @required($required)>
        <input
            type="search"
            x-model="term"
            x-on:input.debounce.180ms="search()"
            x-on:keydown.enter.prevent="chooseExactOrFirst()"
            x-on:focus="term && search()"
            autocomplete="off"
            inputmode="search"
            class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"
            placeholder="Scan barcode, or type product name / SKU"
        >
    </label>
    <div x-show="open" x-cloak class="relative z-30 mt-1 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
        <template x-for="item in results" :key="item.id">
            <button type="button" x-on:click="select(item)" class="flex w-full min-w-0 items-start justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-0 hover:bg-slate-50">
                <span class="min-w-0">
                    <strong class="block truncate text-sm text-slate-900" x-text="item.name"></strong>
                    <span class="block truncate text-xs text-slate-500" x-text="[item.sku, item.barcode].filter(Boolean).join(' · ')"></span>
                </span>
                @if ($showAvailability)
                    <span class="shrink-0 text-right text-xs text-slate-600">
                        <span class="block font-semibold" x-text="`₦${Number(item.price).toLocaleString(undefined,{minimumFractionDigits:2})}`"></span>
                        <span class="block" x-text="item.track_inventory ? `${item.quantity} available` : 'Untracked'"></span>
                    </span>
                @endif
            </button>
        </template>
        <p x-show="!loading && results.length === 0" class="p-3 text-sm text-slate-500">No matching product in this branch.</p>
    </div>
    <p x-show="selected" class="mt-2 truncate text-xs text-slate-600" x-text="selected ? `${selected.name} · ${selected.sku}` : ''"></p>
</div>
BLADE

cat >> resources/js/app.js <<'JS'

document.addEventListener('alpine:init', () => {
    Alpine.data('productScanner', ({ endpoint, context, branchField }) => ({
        term: '', results: [], selected: null, selectedId: '', open: false, loading: false,
        branchId() {
            const field = document.querySelector(`[name="${branchField}"]`);
            return field ? field.value : '';
        },
        async search() {
            this.selected = null; this.selectedId = '';
            if (!this.term.trim() || !this.branchId()) { this.results = []; this.open = false; return; }
            this.loading = true;
            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('q', this.term.trim());
                url.searchParams.set('branch_id', this.branchId());
                url.searchParams.set('context', context);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) { this.results = []; this.open = false; return; }
                this.results = (await response.json()).data || [];
                this.open = true;
            } finally { this.loading = false; }
        },
        chooseExactOrFirst() {
            const q = this.term.trim().toLowerCase();
            const item = this.results.find(i => (i.barcode || '').toLowerCase() === q || (i.sku || '').toLowerCase() === q) || this.results[0];
            if (item) this.select(item); else this.search();
        },
        select(item) {
            this.selected = item; this.selectedId = item.id; this.term = item.name; this.open = false;
            this.$dispatch('product-selected', { ...item });
        },
        close() { this.open = false; },
    }));
});
JS

python3 <<'PY'
from pathlib import Path
p=Path('routes/web.php')
s=p.read_text()
use='use App\\Http\\Controllers\\Catalog\\ProductLookupController;\n'
if use not in s:
    s=s.replace("use App\\Http\\Controllers\\Public\\SaleVerificationController;\n", "use App\\Http\\Controllers\\Public\\SaleVerificationController;\n"+use)
route="""
Route::get('/catalog/products/lookup', ProductLookupController::class)
    ->middleware(['auth', 'account.active', 'session.inactivity'])
    ->name('catalog.products.lookup');
"""
if "catalog.products.lookup" not in s:
    s += route
p.write_text(s)
PY

echo "[6/12] Rebuilding sales flow around branch-first scanning, price and availability"
cat > resources/views/admin/sales/create.blade.php <<'BLADE'
<x-layout.app title="New sale | Express Cloud">
    <x-layout.app-shell page-title="New sale" page-description="Choose a branch, then scan or search products using barcode, SKU, or name.">
        <form method="POST" action="{{ route('admin.sales.store') }}" class="min-w-0 space-y-6" x-data="saleBuilder()">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <x-ui.card title="Sale details">
                <div class="grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <label class="block min-w-0"><span class="mb-2 block text-sm font-medium text-slate-700">Sale type</span><select name="sale_type" required class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"><option value="invoice">Invoice</option><option value="quote">Quote</option><option value="pos">POS</option></select></label>
                    <label class="block min-w-0"><span class="mb-2 block text-sm font-medium text-slate-700">Branch</span><select name="branch_id" required x-model="branchId" x-on:change="resetLines()" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"><option value="">Select branch first</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
                    <label class="block min-w-0"><span class="mb-2 block text-sm font-medium text-slate-700">Customer</span><input type="search" placeholder="Search customer or leave blank for walk-in" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm" data-customer-search><input type="hidden" name="customer_id" data-customer-id></label>
                </div>
            </x-ui.card>

            <x-ui.card title="Products" description="USB and Bluetooth barcode scanners work like keyboard input. Select the branch before scanning.">
                <div x-show="!branchId" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">Select the sale branch before scanning products.</div>
                <div x-show="branchId" class="min-w-0 space-y-4">
                    <x-catalog.product-scanner name="scanner_product_id" branch-field="branch_id" context="sale" />
                    <div class="ec-responsive-table" x-show="lines.length">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead><tr class="border-b text-left"><th class="p-3">Product</th><th class="p-3">Available</th><th class="p-3">Quantity</th><th class="p-3">Branch price</th><th class="p-3">Discount</th><th class="p-3"></th></tr></thead>
                            <tbody><template x-for="(line,index) in lines" :key="line.key"><tr class="border-b align-top"><td class="p-3"><input type="hidden" :name="`items[${index}][product_id]`" :value="line.id"><strong x-text="line.name"></strong><small class="block text-slate-500" x-text="line.sku"></small></td><td class="p-3" x-text="line.track_inventory ? line.quantity : 'Untracked'"></td><td class="p-3"><input :name="`items[${index}][quantity]`" x-model="line.saleQuantity" required class="w-28 rounded-lg border p-2"></td><td class="p-3"><input :name="`items[${index}][unit_price]`" x-model="line.price" type="number" step="0.01" readonly class="w-32 rounded-lg border bg-slate-50 p-2"></td><td class="p-3"><input :name="`items[${index}][discount]`" value="0" type="number" step="0.01" class="w-28 rounded-lg border p-2"></td><td class="p-3"><button type="button" x-on:click="lines.splice(index,1)" class="font-semibold text-red-700">Remove</button></td></tr></template></tbody>
                        </table>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Payments" description="Quotes ignore payments until converted. Invoices may be unpaid or partially paid."><div class="grid min-w-0 gap-3 md:grid-cols-3"><select name="payments[0][payment_method_id]" class="min-h-11 min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm"><option value="">Payment method</option>@foreach ($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select><input name="payments[0][amount]" type="number" step="0.01" class="min-h-11 min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Amount"><input name="payments[0][reference]" class="min-h-11 min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm" placeholder="Reference"></div></x-ui.card>
            <x-ui.card title="Notes"><textarea name="notes" class="min-h-28 w-full min-w-0 rounded-lg border border-slate-300 p-3 text-sm"></textarea></x-ui.card>
            <div class="flex justify-end"><x-ui.button type="submit">Complete sale</x-ui.button></div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
<script>
function saleBuilder(){return{branchId:'',lines:[],init(){this.$el.addEventListener('product-selected',e=>this.add(e.detail));},add(item){const existing=this.lines.find(l=>l.id===item.id);if(existing){existing.saleQuantity=String(Number(existing.saleQuantity)+1);return;}this.lines.push({...item,key:item.id+'-'+Date.now(),saleQuantity:'1'});},resetLines(){this.lines=[];}}}
</script>
BLADE

# Controller: only branches assigned to the actor are selectable; no full product dump is sent to the browser.
python3 <<'PY'
from pathlib import Path
p=Path('app/Http/Controllers/Admin/Sales/SaleController.php')
s=p.read_text()
s=s.replace('    public function create(): View\n    {', '    public function create(Request $request): View\n    {')
start=s.find("            'branches' => Branch::query()", s.find('public function create'))
end=s.find("            'paymentMethods' =>", start)
if start!=-1 and end!=-1:
    replacement="""            'branches' => Branch::query()
                ->where('status', 'active')
                ->when(! $request->user()->is_allowed_all_branches, fn ($query) => $query->whereIn('id', $request->user()->branches()->select('branches.id')))
                ->orderBy('name')->get(['id', 'name']),
"""
    s=s[:start]+replacement+s[end:]
# Scope index to own sales unless explicit all-sales permission.
needle="        return view('admin.sales.index', [\n            'sales' => Sale::query()"
if needle in s:
    s=s.replace(needle, "        /** @var Account $actor */\n        $actor = $request->user();\n\n        return view('admin.sales.index', [\n            'sales' => Sale::query()\n                ->when(! $actor->can('sales.view.all'), fn ($query) => $query->where('sold_by_account_id', $actor->getKey()))")
p.write_text(s)
PY

# Enforce branch access and stock policy during sale creation.
python3 <<'PY'
from pathlib import Path
p=Path('app/Actions/Sales/CreateSale.php')
s=p.read_text()
if 'use App\\Services\\Organisation\\BranchAccess;' not in s:
    # insert with other use statements
    pos=s.find('use Illuminate')
    s=s[:pos]+'use App\\Services\\Organisation\\BranchAccess;\n'+s[pos:]
# Add constructor dependency if conventional constructor exists.
if 'private BranchAccess $branchAccess' not in s:
    s=s.replace('private AuditLogger $audit,', 'private AuditLogger $audit,\n        private BranchAccess $branchAccess,')
# enforce after branch resolution by finding first Branch findOrFail block closing.
marker="$branch = Branch::query()->findOrFail("
idx=s.find(marker)
if idx!=-1:
    sem=s.find(';', idx)
    if sem!=-1 and '$this->branchAccess->enforce' not in s[sem:sem+200]:
        s=s[:sem+1]+"\n            $this->branchAccess->enforce($actor, $branch);"+s[sem+1:]
# Branch price instead of default price.
s=s.replace("                ) ?? $product->default_price_kobo;", "                ) ?? (int) ($product->branchPrices()->where('branch_id', $branch->getKey())->value('price_kobo') ?? $product->default_price_kobo);")
p.write_text(s)
PY

echo "[7/12] Adding zero-stock policy and bulk branch-price adjustment"
MIGRATION="database/migrations/2026_07_17_000001_add_zero_stock_policy_to_branches.php"
cat > "$MIGRATION" <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('branches', fn (Blueprint $table) => $table->boolean('allow_zero_stock_sales')->default(false)->after('status')); }
    public function down(): void { Schema::table('branches', fn (Blueprint $table) => $table->dropColumn('allow_zero_stock_sales')); }
};
PHP

mkdir -p app/Http/Controllers/Admin/Catalog app/Http/Requests/Admin/Catalog resources/views/admin/catalog
cat > app/Http/Requests/Admin/Catalog/BulkPriceAdjustmentRequest.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkPriceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
        'direction' => ['required', Rule::in(['add','subtract'])],
        'mode' => ['required', Rule::in(['percentage','fixed'])],
        'value' => ['required','numeric','min:0'],
        'all_branches' => ['nullable','boolean'], 'branch_ids' => ['array'], 'branch_ids.*' => ['ulid','exists:branches,id'],
        'all_products' => ['nullable','boolean'], 'product_ids' => ['array'], 'product_ids.*' => ['ulid','exists:products,id'],
    ]; }
}
PHP

cat > app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\BulkPriceAdjustmentRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Services\Organisation\BranchAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class ProductPriceAdjustmentController
{
    public function __construct(private BranchAccess $branchAccess) {}
    public function index(): View { return view('admin.catalog.price-adjustments', ['branches'=>Branch::query()->where('status','active')->orderBy('name')->get(['id','name'])]); }
    public function store(BulkPriceAdjustmentRequest $request): RedirectResponse
    {
        /** @var Account $actor */ $actor=$request->user();
        $branchIds=$request->boolean('all_branches')
            ? Branch::query()->where('status','active')->pluck('id')->all()
            : $request->array('branch_ids');
        foreach($branchIds as $branchId) $this->branchAccess->enforce($actor,(string)$branchId);
        $productIds=$request->boolean('all_products') ? Product::query()->where('status','active')->pluck('id')->all() : $request->array('product_ids');
        abort_if($branchIds===[] || $productIds===[],422,'Select at least one branch and product.');
        DB::transaction(function() use($request,$branchIds,$productIds):void{
            foreach(Product::query()->whereKey($productIds)->cursor() as $product){
                foreach($branchIds as $branchId){
                    $current=(int)(ProductBranchPrice::query()->where('product_id',$product->getKey())->where('branch_id',$branchId)->value('price_kobo') ?? $product->default_price_kobo);
                    $delta=$request->string('mode')->toString()==='percentage' ? (int)round($current*((float)$request->input('value')/100)) : (int)round((float)$request->input('value')*100);
                    $next=$request->string('direction')->toString()==='subtract' ? max(0,$current-$delta) : $current+$delta;
                    ProductBranchPrice::query()->updateOrCreate(['product_id'=>$product->getKey(),'branch_id'=>$branchId],['price_kobo'=>$next]);
                }
            }
        });
        return back()->with('status','Branch prices updated.');
    }
}
PHP

cat > resources/views/admin/catalog/price-adjustments.blade.php <<'BLADE'
<x-layout.app title="Price adjustments | Express Cloud"><x-layout.app-shell page-title="Bulk product price adjustment" page-description="Increase or decrease prices by fixed value or percentage across selected products and branches."><form method="POST" action="{{ route('admin.catalog.price-adjustments.store') }}" class="space-y-6" x-data="{allBranches:false,allProducts:false}">@csrf<x-ui.card title="Adjustment"><div class="grid min-w-0 gap-4 md:grid-cols-2"><select name="direction" required class="min-h-11 rounded-lg border px-3"><option value="add">Add</option><option value="subtract">Subtract</option></select><select name="mode" required class="min-h-11 rounded-lg border px-3"><option value="percentage">Percentage</option><option value="fixed">Fixed amount</option></select><input name="value" type="number" step="0.01" min="0" required class="min-h-11 rounded-lg border px-3" placeholder="Value"><label><input type="checkbox" name="all_branches" value="1" x-model="allBranches"> All accessible branches</label><div x-show="!allBranches" class="max-h-52 overflow-y-auto rounded-lg border p-3">@foreach($branches as $branch)<label class="block py-1"><input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"> {{ $branch->name }}</label>@endforeach</div><label><input type="checkbox" name="all_products" value="1" x-model="allProducts"> All products</label><div x-show="!allProducts"><x-catalog.product-scanner name="product_ids[]" branch-field="branch_ids[]" context="inventory" label="Scan or search a product" :show-availability="false" /></div></div></x-ui.card><x-ui.button type="submit">Apply price adjustment</x-ui.button></form></x-layout.app-shell></x-layout.app>
BLADE

python3 <<'PY'
from pathlib import Path
p=Path('routes/admin.php')
s=p.read_text()
use='use App\\Http\\Controllers\\Admin\\Catalog\\ProductPriceAdjustmentController;\n'
if use not in s:
    anchor='use App\\Http\\Controllers\\Admin\\Catalog\\ProductController;\n'
    s=s.replace(anchor, anchor+use)
route="""
        Route::get('/price-adjustments', [ProductPriceAdjustmentController::class, 'index'])
            ->middleware('permission:products.prices.adjust')->name('price-adjustments.index');
        Route::post('/price-adjustments', [ProductPriceAdjustmentController::class, 'store'])
            ->middleware('permission:products.prices.adjust')->name('price-adjustments.store');
"""
marker="Route::prefix('catalog')->name('catalog.')->group(function (): void {"
if "price-adjustments.store" not in s:
    s=s.replace(marker, marker+'\n'+route)
p.write_text(s)
PY

# Do not permit overselling unless the branch policy is enabled.
python3 <<'PY'
from pathlib import Path
p=Path('app/Actions/Sales/CreateSale.php')
s=p.read_text()
needle="                if ($type->movesStock() && $product->track_inventory) {"
insert="""                if ($type->movesStock() && $product->track_inventory) {
                    $available = (int) ($product->branchStock()->where('branch_id', $branch->getKey())->value('quantity_milliunits') ?? 0);
                    if (! (bool) $branch->allow_zero_stock_sales && $quantityMilliunits > $available) {
                        throw new \\DomainException('Insufficient branch stock for '.$product->name.'.');
                    }
"""
if needle in s and '$branch->allow_zero_stock_sales' not in s:
    s=s.replace(needle, insert)
p.write_text(s)
PY

echo "[8/12] Installing scanner controls in every product operation"
python3 <<'PY'
from pathlib import Path
p=Path('resources/views/admin/inventory/index.blade.php')
if p.exists():
    s=p.read_text()
    import re
    # Replace each product select in inventory operation forms with the universal scanner.
    s=re.sub(r'<select name="product_id" required[^>]*>.*?</select>', '<x-catalog.product-scanner name="product_id" branch-field="branch_id" context="inventory" />', s, flags=re.S)
    # Transfer uses source branch.
    first=s.find('<x-catalog.product-scanner')
    # Any scanner inside transfer form can resolve against source branch when this field exists.
    transfer_start=s.find("route('admin.inventory.transfer')")
    if transfer_start!=-1:
        scanner=s.find('<x-catalog.product-scanner', transfer_start)
        if scanner!=-1:
            end=s.find('/>',scanner)
            block=s[scanner:end+2].replace('branch-field="branch_id"','branch-field="source_branch_id"')
            s=s[:scanner]+block+s[end+2:]
    p.write_text(s)

# Product create barcode field becomes scanner-friendly and autofocusable.
p=Path('resources/views/admin/catalog/products/create.blade.php')
if p.exists():
    s=p.read_text().replace('name="barcode"', 'name="barcode" inputmode="numeric" autocomplete="off" data-barcode-input')
    p.write_text(s)
PY

# Product operations remain route-protected by their own permissions. Sales only gets the sale lookup route, never inventory screens.

echo "[9/12] Tightening admin access-key recovery"
python3 <<'PY'
from pathlib import Path

p=Path('app/Http/Controllers/Admin/StaffController.php')
s=p.read_text()
if 'public function revealAccessKey(' not in s:
    method='''
    public function revealAccessKey(
        Request $request,
        Account $account,
    ): RedirectResponse {
        abort_if($account->login_key_encrypted === null, 404);

        $plainKey = $this->encryptedValue->decrypt(
            (string) $account->login_key_encrypted,
        );

        $this->audit->record(
            $request,
            'staff.access-key.revealed',
            'account',
            $account,
            after: ['account_public_id' => $account->public_id],
        );

        return back()->with('revealed_access_key', [
            'account_id' => (string) $account->getKey(),
            'account_name' => $account->displayName(),
            'access_key' => $plainKey,
        ]);
    }
'''
    s=s.rsplit('}',1)[0]+method+'}\n'
p.write_text(s)

p=Path('routes/admin.php')
s=p.read_text()
if "name('staff.access-key.reveal')" not in s:
    marker="    Route::get('/staff', [StaffController::class, 'index'])"
    route="""    Route::post('/staff/{account}/access-key/reveal', [StaffController::class, 'revealAccessKey'])
        ->middleware('permission:staff.access-key.reveal')
        ->name('staff.access-key.reveal');

"""
    s=s.replace(marker, route+marker)
p.write_text(s)

p=Path('resources/views/admin/staff/index.blade.php')
if p.exists():
    s=p.read_text()
    if "session('revealed_access_key')" not in s:
        panel='''
@if (session('revealed_access_key'))
    <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4">
        <p class="text-sm font-semibold text-amber-900">Access key for {{ session('revealed_access_key.account_name') }}</p>
        <code class="mt-2 block break-all rounded-lg bg-white p-3 text-sm">{{ session('revealed_access_key.access_key') }}</code>
        <p class="mt-2 text-xs text-amber-800">This reveal was added to the audit log.</p>
    </div>
@endif
'''
        s=s.replace('<x-layout.app-shell', panel+'\n<x-layout.app-shell',1)
    p.write_text(s)
PY
echo "[10/12] Adding universal product-operation quantity steppers"
cat >> resources/js/app.js <<'JS'

/**
 * Progressive quantity controls for every server-rendered product operation.
 * Applies to sales, stock intake, transfers, adjustments, purchasing, returns,
 * and any future product form using a conventional quantity/qty/units field.
 */
(function installProductQuantitySteppers() {
    const quantityName = /(^|\[|_)(quantity|qty|units|milliunits)(\]|_|$)/i;

    function precision(value) {
        const text = String(value ?? '');
        return text.includes('.') ? text.split('.')[1].length : 0;
    }

    function enhance(input) {
        if (!(input instanceof HTMLInputElement)) return;
        if (input.dataset.quantityStepperReady === '1') return;
        if (!input.name || !quantityName.test(input.name)) return;
        if (input.type === 'hidden' || input.disabled || input.readOnly) return;
        if (!['number', 'text', 'search'].includes(input.type)) return;

        input.dataset.quantityStepperReady = '1';
        input.inputMode = input.inputMode || 'decimal';

        const wrapper = document.createElement('div');
        wrapper.className = 'ec-quantity-stepper';
        wrapper.setAttribute('role', 'group');
        wrapper.setAttribute('aria-label', 'Quantity controls');

        const minus = document.createElement('button');
        minus.type = 'button';
        minus.className = 'ec-quantity-stepper__button';
        minus.setAttribute('aria-label', 'Decrease quantity');
        minus.textContent = '−';

        const plus = document.createElement('button');
        plus.type = 'button';
        plus.className = 'ec-quantity-stepper__button';
        plus.setAttribute('aria-label', 'Increase quantity');
        plus.textContent = '+';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.append(minus, input, plus);
        input.classList.add('ec-quantity-stepper__input');

        const update = (direction) => {
            const configuredStep = Number(input.step);
            const step = Number.isFinite(configuredStep) && configuredStep > 0
                ? configuredStep
                : /milliunits/i.test(input.name) ? 1000 : 1;
            const minimum = input.min !== '' ? Number(input.min) : 0;
            const maximum = input.max !== '' ? Number(input.max) : Number.POSITIVE_INFINITY;
            const current = Number(input.value || 0);
            const next = Math.min(maximum, Math.max(minimum, current + (direction * step)));
            const places = Math.max(precision(step), precision(input.value));

            input.value = places > 0 ? next.toFixed(places) : String(Math.round(next));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        minus.addEventListener('click', () => update(-1));
        plus.addEventListener('click', () => update(1));
    }

    function scan(root = document) {
        root.querySelectorAll('input[name]').forEach(enhance);
    }

    document.addEventListener('DOMContentLoaded', () => scan());
    document.addEventListener('alpine:initialized', () => scan());

    new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (!(node instanceof Element)) continue;
                if (node.matches?.('input[name]')) enhance(node);
                scan(node);
            }
        }
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
JS

cat >> resources/css/app.css <<'CSS'

/* Universal product quantity stepper */
.ec-quantity-stepper {
  display: inline-grid;
  grid-template-columns: 2.5rem minmax(4.5rem, 1fr) 2.5rem;
  align-items: stretch;
  width: min(100%, 11rem);
  min-width: 0;
  overflow: hidden;
  border: 1px solid rgb(203 213 225);
  border-radius: .5rem;
  background: #fff;
}
.ec-quantity-stepper__button {
  display: grid;
  place-items: center;
  min-width: 0;
  border: 0;
  background: rgb(248 250 252);
  color: rgb(15 23 42);
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1;
  cursor: pointer;
  touch-action: manipulation;
}
.ec-quantity-stepper__button:hover { background: rgb(241 245 249); }
.ec-quantity-stepper__button:focus-visible { outline: 2px solid currentColor; outline-offset: -2px; }
.ec-quantity-stepper__input {
  width: 100% !important;
  min-width: 0 !important;
  border: 0 !important;
  border-inline: 1px solid rgb(203 213 225) !important;
  border-radius: 0 !important;
  text-align: center;
  appearance: textfield;
}
.ec-quantity-stepper__input::-webkit-inner-spin-button,
.ec-quantity-stepper__input::-webkit-outer-spin-button { appearance: none; margin: 0; }
@media (max-width: 640px) {
  .ec-quantity-stepper { width: 100%; max-width: 12rem; }
}
CSS

echo "[11/13] Fixing top bar, body, cards and two-column overflow without clipping content"
cat >> resources/css/app.css <<'CSS'

/* Post-live responsive containment */
html, body { width: 100%; max-width: 100%; min-width: 0; }
body { overflow-x: hidden; }
*, *::before, *::after { box-sizing: border-box; }
img, svg, video, canvas { max-width: 100%; height: auto; }
.ec-app, .ec-shell, .ec-main, main, .ec-page, .ec-content { width: 100%; max-width: 100%; min-width: 0; }
.ec-topbar { width: 100%; max-width: 100%; min-width: 0; }
.ec-topbar > *, .ec-card, .ec-card > *, form, fieldset, .grid, .flex { min-width: 0; }
.ec-card { max-width: 100%; overflow-wrap: anywhere; }
.ec-responsive-table { width: 100%; max-width: 100%; overflow-x: auto; overscroll-behavior-inline: contain; -webkit-overflow-scrolling: touch; }
.ec-responsive-table > table { width: 100%; }
.ec-two-column, .ec-dashboard-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
@media (min-width: 1024px) {
  .ec-two-column, .ec-dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767px) {
  .ec-topbar { position: relative; inset-inline: 0; width: 100%; }
  .ec-topbar .flex { flex-wrap: wrap; }
  .ec-card { border-radius: .75rem; }
  .ec-page, .ec-content, main { padding-inline: .75rem; }
  input, select, textarea, button { max-width: 100%; }
}
CSS

# Add minmax containment to common Blade grids that generated body-level horizontal scrolling.
python3 <<'PY'
from pathlib import Path
for p in Path('resources/views').rglob('*.blade.php'):
    s=p.read_text()
    s=s.replace('grid-cols-2', 'grid-cols-[repeat(2,minmax(0,1fr))]')
    s=s.replace('lg:grid-cols-2', 'lg:grid-cols-[repeat(2,minmax(0,1fr))]')
    s=s.replace('xl:grid-cols-2', 'xl:grid-cols-[repeat(2,minmax(0,1fr))]')
    # Ensure wide tables scroll locally, not at body level.
    s=s.replace('<div class="overflow-x-auto">', '<div class="ec-responsive-table">')
    p.write_text(s)
PY

echo "[12/13] Moving the web entry point to repository root while preserving Vite assets in public"
cp public/index.php index.php
python3 <<'PY'
from pathlib import Path
p=Path('index.php')
s=p.read_text()
s=s.replace("__DIR__.'/../vendor/autoload.php'", "__DIR__.'/vendor/autoload.php'")
s=s.replace("__DIR__.'/../bootstrap/app.php'", "__DIR__.'/bootstrap/app.php'")
s=s.replace("__DIR__.'/../storage/framework/maintenance.php'", "__DIR__.'/storage/framework/maintenance.php'")
p.write_text(s)
PY
[[ -f public/robots.txt ]] && cp public/robots.txt robots.txt || true
cat > .htaccess <<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Keep Vite output and static assets in /public while the front controller lives here.
    RewriteRule ^build/(.*)$ public/build/$1 [L]
    RewriteRule ^favicon\.ico$ public/favicon.ico [L]
    RewriteRule ^images/(.*)$ public/images/$1 [L]
    RewriteRule ^storage/(.*)$ public/storage/$1 [L]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS

# The original public entry files can remain for Vite/developer-server compatibility; root is now deployable.

echo "[13/13] Validating, rebuilding and packaging"
php_files=(
  app/Services/Dashboard/StaffDashboardData.php
  app/Services/Organisation/AuthorizationService.php
  app/Support/Authorization/RolePermissionPolicy.php
  app/Http/Controllers/Admin/RoleController.php
  app/Services/Organisation/BranchAccess.php
  app/Services/Catalog/ProductLookup.php
  app/Http/Controllers/Catalog/ProductLookupController.php
  app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php
  app/Http/Requests/Admin/Catalog/BulkPriceAdjustmentRequest.php
  "$MIGRATION" index.php
)
for file in "${php_files[@]}"; do php -l "$file" >/dev/null; done

php artisan optimize:clear
php artisan route:list >/dev/null
php artisan test
npm ci
npm run build
php artisan optimize:clear

# Verify removed API and invalid schema references remain absent.
! php artisan route:list | grep -E '(^|[[:space:]])api/'
! search_php "product_branch_stocks|reorder_level_milliunits" app routes resources database/migrations

ARCHIVE="$RELEASE_DIR/express-cloud-post-live-strengthened-$STAMP.zip"
zip -qr "$ARCHIVE" . \
  -x '.git/*' 'node_modules/*' 'storage/logs/*' 'storage/framework/cache/*' \
     'storage/app/patch-backups/*' 'release/*.zip' '.env'

echo "[done] Backup: $BACKUP_DIR"
echo "[done] Package: $ARCHIVE"
echo "[next] Run production migration after deployment: php artisan migrate --force"
