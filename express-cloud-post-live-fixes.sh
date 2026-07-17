#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${1:-$(pwd)}"
cd "$APP_DIR"

required=(artisan composer.json package.json app resources routes config)
for path in "${required[@]}"; do
  [[ -e "$path" ]] || { echo "[error] Run this from the Express Cloud repository root (missing: $path)" >&2; exit 1; }
done

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="storage/app/patch-backups/post-live-$STAMP"
mkdir -p "$BACKUP_DIR"

backup() {
  local file="$1"
  [[ -f "$file" ]] || return 0
  mkdir -p "$BACKUP_DIR/$(dirname "$file")"
  cp "$file" "$BACKUP_DIR/$file"
}

files=(
  app/Services/Dashboard/StaffDashboardData.php
  resources/views/staff/dashboard.blade.php
  app/Services/Organisation/AuthorizationService.php
  app/Http/Controllers/Admin/RoleController.php
  app/Http/Controllers/Admin/StaffController.php
  resources/views/admin/staff/index.blade.php
  resources/views/admin/sales/create.blade.php
  resources/views/admin/catalog/products/create.blade.php
  resources/views/admin/inventory/index.blade.php
  resources/views/components/layout/app-shell.blade.php
  resources/views/components/navigation/topbar.blade.php
  resources/css/app.css
  resources/js/app.js
  routes/admin.php
)
for file in "${files[@]}"; do backup "$file"; done

echo "[1/9] Applying production dashboard schema corrections"
cat > app/Services/Dashboard/StaffDashboardData.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Account;
use App\Services\Organisation\AuthorizationService;
use Illuminate\Support\Facades\DB;

final readonly class StaffDashboardData
{
    public function __construct(
        private AuthorizationService $authorization,
    ) {}

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
                'id',
                'sale_code',
                'sale_type',
                'status',
                'sale_date',
                'grand_total_kobo',
                'paid_amount_kobo',
            ])
            ->map(function (object $sale): object {
                $sale->balance_due_kobo = max(
                    0,
                    (int) $sale->grand_total_kobo - (int) $sale->paid_amount_kobo,
                );

                return $sale;
            });

        $outstandingKobo = (int) (clone $sales)
            ->selectRaw(
                'COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS outstanding_total',
            )
            ->value('outstanding_total');

        return [
            'todaySalesCount' => (clone $sales)
                ->whereDate('sale_date', $today)
                ->count(),
            'todayRevenueKobo' => (int) (clone $sales)
                ->whereDate('sale_date', $today)
                ->sum('grand_total_kobo'),
            'monthRevenueKobo' => (int) (clone $sales)
                ->whereBetween('sale_date', [$monthStart, $today])
                ->sum('grand_total_kobo'),
            'outstandingKobo' => $outstandingKobo,
            'recentSales' => $recentSales,
            'permissions' => $this->authorization->permissionSlugs($account),
        ];
    }
}
PHP

python3 <<'PY'
from pathlib import Path
p=Path('resources/views/staff/dashboard.blade.php')
s=p.read_text()
s=s.replace('{{ $sale->reference }}','{{ $sale->sale_code }}')
# Staff must never receive inventory/low-stock UI.
import re
s=re.sub(r"\s*@if\(\$permissions->contains\('inventory\.view'\)\).*?@endif", "", s, flags=re.S)
s=re.sub(r"\s*@if\(\$permissions->intersect\(\['inventory\.view','reports\.low-stock'\]\)->isNotEmpty\(\)\).*?@endif", "", s, flags=re.S)
p.write_text(s)
PY

echo "[2/9] Adding role-aware permission boundaries"
mkdir -p app/Support/Authorization
cat > app/Support/Authorization/RolePermissionPolicy.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Authorization;

final class RolePermissionPolicy
{
    /** @var list<string> */
    private const SALES_STAFF_ALLOWED = [
        'sales.view.own',
        'sales.create',
        'sales.payments.record',
        'sales.returns.create',
        'vouchers.apply',
        'quotes.convert',
        'customers.view',
        'customers.create',
        'customers.update',
        'documents.sales.print',
    ];

    /** @param list<string> $requested @return list<string> */
    public static function constrain(string $name, string $slug, array $requested): array
    {
        $identity = mb_strtolower(trim($name.' '.$slug));

        if (preg_match('/\b(sales|cashier|salesperson|sales-rep|sales_rep|sales-staff)\b/u', $identity) === 1) {
            return array_values(array_intersect($requested, self::SALES_STAFF_ALLOWED));
        }

        return array_values(array_unique($requested));
    }

    /** @return list<string> */
    public static function salesStaffDefaults(): array
    {
        return self::SALES_STAFF_ALLOWED;
    }
}
PHP

python3 <<'PY'
from pathlib import Path
p=Path('app/Http/Controllers/Admin/RoleController.php')
s=p.read_text()
s=s.replace('use App\\Support\\Authorization\\PermissionCatalog;', 'use App\\Support\\Authorization\\PermissionCatalog;\nuse App\\Support\\Authorization\\RolePermissionPolicy;')
s=s.replace("$permissionIds = Permission::query()\n                ->whereIn(\n                    'slug',\n                    $request->array('permissions'),\n                )", "$requestedPermissions = RolePermissionPolicy::constrain(\n                $request->string('name')->toString(),\n                $request->string('slug')->toString(),\n                $request->array('permissions'),\n            );\n\n            $permissionIds = Permission::query()\n                ->whereIn('slug', $requestedPermissions)")
s=s.replace("'permissions' => $request->array('permissions'),", "'permissions' => RolePermissionPolicy::constrain(\n                    $request->string('name')->toString(),\n                    $request->string('slug')->toString(),\n                    $request->array('permissions'),\n                ),")
p.write_text(s)
PY

# Runtime hard boundary: a sales-like role can never inherit catalogue, stock, imports,
# procurement, business settings, staff administration, accounting, backups or security.
python3 <<'PY'
from pathlib import Path
p=Path('app/Services/Organisation/AuthorizationService.php')
s=p.read_text()
old="""        $allowed = $account->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', static function ($query) use ($permission): void {
                $query->where('permissions.slug', $permission);
            })
            ->exists();
"""
new="""        $roles = $account->roles()
            ->where('roles.is_active', true)
            ->with('permissions:id,slug')
            ->get();

        $hasSalesRole = $roles->contains(static function ($role): bool {
            $identity = mb_strtolower(trim($role->name.' '.$role->slug));

            return preg_match('/\\b(sales|cashier|salesperson|sales-rep|sales_rep|sales-staff)\\b/u', $identity) === 1;
        });

        if ($hasSalesRole) {
            $allowedForSalesStaff = in_array($permission, [
                'sales.view.own',
                'sales.create',
                'sales.payments.record',
                'sales.returns.create',
                'vouchers.apply',
                'quotes.convert',
                'customers.view',
                'customers.create',
                'customers.update',
                'documents.sales.print',
            ], true);

            if (! $allowedForSalesStaff) {
                return $this->permissionCache[$accountId][$permission] = false;
            }
        }

        $allowed = $roles->contains(
            static fn ($role): bool => $role->permissions->contains('slug', $permission),
        );
"""
if old not in s:
    raise SystemExit('AuthorizationService expected block not found')
s=s.replace(old,new)
p.write_text(s)
PY

echo "[3/9] Adding authorised access-key reveal for administrators"
python3 <<'PY'
from pathlib import Path
p=Path('app/Http/Controllers/Admin/StaffController.php')
s=p.read_text()
insert="""
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
            'name' => $account->displayName(),
            'key' => $plainKey,
        ]);
    }

"""
needle='    public function suspend(\n'
if insert.strip() not in s:
    s=s.replace(needle, insert+needle)
p.write_text(s)

p=Path('routes/admin.php')
s=p.read_text()
needle="""    Route::patch(
        '/staff/{account}/suspend',
"""
route="""    Route::post(
        '/staff/{account}/access-key/reveal',
        [StaffController::class, 'revealAccessKey'],
    )
        ->middleware('permission:staff.access-key.reveal')
        ->name('staff.access-key.reveal');

"""
if route.strip() not in s:
    s=s.replace(needle, route+needle)
p.write_text(s)
PY

python3 <<'PY'
from pathlib import Path
p=Path('resources/views/admin/staff/index.blade.php')
s=p.read_text()
marker="""        @if (session('generated_access_key'))
"""
block="""        @if (session('revealed_access_key'))
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm font-semibold text-amber-900">Access key for {{ session('revealed_access_key.name') }}</p>
                <p class="mt-2 break-all font-mono text-xl font-bold tracking-[0.1em] text-amber-950">{{ session('revealed_access_key.key') }}</p>
                <p class="mt-2 text-xs text-amber-800">This reveal was permission-checked and written to the audit log.</p>
            </div>
        @endif

"""
if block.strip() not in s:
    s=s.replace(marker, block+marker)
needle="""                                    @if ($account->status->value === 'active')
"""
button="""                                    @if (auth()->user() && app(\\App\\Services\\Organisation\\AuthorizationService::class)->hasPermission(auth()->user(), 'staff.access-key.reveal'))
                                        <form method="POST" action="{{ route('admin.staff.access-key.reveal', $account) }}">
                                            @csrf
                                            <x-ui.button type="submit" variant="ghost">Reveal key</x-ui.button>
                                        </form>
                                    @endif
"""
if button.strip() not in s:
    s=s.replace(needle, button+needle)
p.write_text(s)
PY

echo "[4/9] Replacing product dropdowns with searchable barcode/name selectors"
cat >> resources/js/app.js <<'JS'

/* Post-live searchable product and barcode controls */
const productFinder = (root) => {
    const input = root.querySelector('[data-product-query]');
    const hidden = root.querySelector('[data-product-id]');
    const results = root.querySelector('[data-product-results]');
    if (!input || !hidden || !results || root.dataset.productFinderReady) return;
    root.dataset.productFinderReady = 'true';

    const products = JSON.parse(root.querySelector('[data-products-json]').textContent || '[]');
    const normalize = (value) => String(value || '').trim().toLowerCase();

    const choose = (product) => {
        hidden.value = product.id;
        input.value = `${product.name} — ${product.sku}${product.barcode ? ` — ${product.barcode}` : ''}`;
        results.replaceChildren();
        results.hidden = true;
        input.dispatchEvent(new CustomEvent('product-selected', { bubbles: true, detail: product }));
    };

    const render = () => {
        const term = normalize(input.value);
        hidden.value = '';
        const matches = products.filter((product) => [product.name, product.sku, product.barcode]
            .some((value) => normalize(value).includes(term))).slice(0, 12);
        results.replaceChildren();
        matches.forEach((product) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ec-product-result';
            button.textContent = `${product.name} — ${product.sku}${product.barcode ? ` — ${product.barcode}` : ''}`;
            button.addEventListener('click', () => choose(product));
            results.appendChild(button);
        });
        results.hidden = matches.length === 0;
        const exact = matches.find((product) => [product.sku, product.barcode]
            .some((value) => normalize(value) === term));
        if (exact) choose(exact);
    };

    input.addEventListener('input', render);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            render();
            results.querySelector('button')?.click();
        }
    });
};

const enhanceProductFinders = (root = document) => {
    root.querySelectorAll('[data-product-finder]').forEach(productFinder);
};

document.addEventListener('DOMContentLoaded', () => enhanceProductFinders());
new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
    if (node instanceof HTMLElement) enhanceProductFinders(node);
}))).observe(document.documentElement, { childList: true, subtree: true });
JS

python3 <<'PY'
from pathlib import Path
p=Path('resources/views/admin/sales/create.blade.php')
s=p.read_text()
old='''                            <select :name="`items[${index}][product_id]`" required class="min-h-11 rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Product or barcode</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} — {{ $product->sku }}{{ $product->barcode ? ' — '.$product->barcode : '' }}
                                    </option>
                                @endforeach
                            </select>'''
new='''                            <div data-product-finder class="relative min-w-0">
                                <input type="search" data-product-query autocomplete="off" placeholder="Scan barcode or type product name / SKU" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                                <input type="hidden" data-product-id :name="`items[${index}][product_id]`" required>
                                <div data-product-results hidden class="ec-product-results"></div>
                                <script type="application/json" data-products-json>@json($products)</script>
                            </div>'''
if old not in s: raise SystemExit('Sale product dropdown not found')
s=s.replace(old,new)
p.write_text(s)
PY

python3 <<'PY'
from pathlib import Path
p=Path('resources/views/admin/inventory/index.blade.php')
s=p.read_text()
def finder(name='product_id'):
 return f'''<div data-product-finder class="relative">
                            <input type="search" data-product-query autocomplete="off" placeholder="Scan barcode or type product name / SKU" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <input type="hidden" data-product-id name="{name}" required>
                            <div data-product-results hidden class="ec-product-results"></div>
                            <script type="application/json" data-products-json>@json($products)</script>
                        </div>'''
import re
s,count=re.subn(r'''<select name="product_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3\.5 text-sm">\s*<option value="">Select product</option>\s*@foreach \(\$products as \$product\).*?@endforeach\s*</select>''', finder(), s, flags=re.S)
if count < 2: raise SystemExit(f'Expected at least two inventory product selectors, replaced {count}')
p.write_text(s)
PY

echo "[5/9] Enabling barcode scan/input on product creation"
python3 <<'PY'
from pathlib import Path
p=Path('resources/views/admin/catalog/products/create.blade.php')
s=p.read_text()
# Add camera-free scanner-friendly hints to existing barcode input by replacing its component.
old='<x-ui.input name="barcode" label="Barcode" />'
new='''<div>
                        <x-ui.input name="barcode" label="Barcode" autocomplete="off" inputmode="numeric" help="Place the cursor here and scan with a USB/Bluetooth barcode scanner, or type the code." />
                        <p class="mt-2 text-xs text-slate-500">Scanner input is accepted directly and Enter moves to the next field.</p>
                    </div>'''
if old in s:
    s=s.replace(old,new)
else:
    # tolerate label variants
    s=s.replace('<x-ui.input name="barcode" label="Product barcode" />',new)
p.write_text(s)
PY

echo "[6/9] Fixing mobile overflow, topbar width and off-canvas cards"
cat >> resources/css/app.css <<'CSS'

/* Post-live responsive containment fixes */
html, body {
    max-width: 100%;
    overflow-x: clip;
}

.ec-app-frame,
.ec-page-main,
.ec-page-content,
main,
main > *,
.grid,
.flex,
[x-data] {
    min-width: 0;
}

.ec-page-main {
    width: 100%;
    max-width: 100vw;
    overflow-x: clip;
}

.ec-page-content {
    width: 100%;
    max-width: 100%;
}

.ec-responsive-table {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overscroll-behavior-inline: contain;
    -webkit-overflow-scrolling: touch;
}

.ec-product-results {
    position: absolute;
    z-index: 70;
    top: calc(100% + .35rem);
    left: 0;
    right: 0;
    max-height: 18rem;
    overflow-y: auto;
    border: 1px solid rgb(203 213 225);
    border-radius: .7rem;
    background: white;
    box-shadow: 0 16px 40px rgb(15 23 42 / .16);
}

.ec-product-result {
    display: block;
    width: 100%;
    padding: .75rem .85rem;
    border-bottom: 1px solid rgb(241 245 249);
    text-align: left;
    font-size: .875rem;
}

.ec-product-result:hover,
.ec-product-result:focus-visible {
    background: rgb(248 250 252);
}

@media (max-width: 639px) {
    .ec-topbar {
        width: 100%;
        max-width: 100vw;
    }

    .ec-page-main {
        padding-inline: 1rem;
    }

    .ec-page-main .rounded-xl,
    .ec-page-main .rounded-lg {
        max-width: 100%;
    }

    input, select, textarea, button {
        max-width: 100%;
    }
}
CSS

python3 <<'PY'
from pathlib import Path
p=Path('resources/views/components/layout/app-shell.blade.php')
s=p.read_text().replace('class="min-h-screen bg-[var(--ec-background)]"','class="ec-app-frame min-h-screen max-w-full overflow-x-clip bg-[var(--ec-background)]"')
s=s.replace('class="min-h-screen transition-[padding] duration-200"','class="ec-page-content min-h-screen max-w-full overflow-x-clip transition-[padding] duration-200"')
s=s.replace('<main class="px-4 py-6 sm:px-6 lg:px-6">','<main class="ec-page-main w-full max-w-full overflow-x-clip px-4 py-6 sm:px-6 lg:px-6">')
p.write_text(s)

p=Path('resources/views/components/navigation/topbar.blade.php')
s=p.read_text().replace('class="sticky top-0 z-40 flex h-16', 'class="ec-topbar sticky top-0 z-40 flex h-16 w-full max-w-full')
p.write_text(s)

# Standardise known table wrappers.
for f in Path('resources/views').rglob('*.blade.php'):
    t=f.read_text()
    t=t.replace('class="overflow-x-auto"','class="ec-responsive-table overflow-x-auto"')
    t=t.replace('class="mt-4 overflow-x-auto"','class="ec-responsive-table mt-4 overflow-x-auto"')
    f.write_text(t)
PY

echo "[7/9] Preventing sales users from seeing stock quantities through shared product payloads"
python3 <<'PY'
from pathlib import Path
# Sale creation payload intentionally contains identity and price only, never stock balances.
p=Path('app/Http/Controllers/Admin/Sales/SaleController.php')
s=p.read_text()
s=s.replace("                    'track_inventory',\n                    'default_price_kobo',", "                    'default_price_kobo',")
p.write_text(s)
PY

echo "[8/9] Validating PHP, rebuilding Vite and clearing caches"
php_files=(
  app/Services/Dashboard/StaffDashboardData.php
  app/Support/Authorization/RolePermissionPolicy.php
  app/Services/Organisation/AuthorizationService.php
  app/Http/Controllers/Admin/RoleController.php
  app/Http/Controllers/Admin/StaffController.php
  routes/admin.php
)
for file in "${php_files[@]}"; do php -l "$file" >/dev/null; done

if [[ -f composer.lock ]]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build
php artisan optimize:clear

# Do not mutate production data automatically. Update permission catalogue rows only.
php artisan db:seed --class=ProductionBootstrapSeeder --force || {
  echo "[warning] ProductionBootstrapSeeder was not run. Existing production data was left untouched." >&2
}

if [[ -x vendor/bin/phpunit ]]; then
  php artisan test --stop-on-failure
fi

echo "[9/9] Packaging corrected application"
mkdir -p release
PACKAGE="release/express-cloud-post-live-$STAMP.zip"
zip -qr "$PACKAGE" . \
  -x '.git/*' 'node_modules/*' 'storage/logs/*' 'storage/framework/cache/*' \
     'storage/framework/sessions/*' 'storage/framework/views/*' 'release/*.zip' \
     '.env' "$BACKUP_DIR/*"

echo
printf '[done] Backup: %s\n' "$BACKUP_DIR"
printf '[done] Package: %s\n' "$PACKAGE"
printf '[done] Dashboard schema, role boundaries, access-key reveal, barcode search/input and mobile containment were applied.\n'
