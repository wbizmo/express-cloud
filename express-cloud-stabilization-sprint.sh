#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

SCRIPT_PATH="$(readlink -f "$0")"
LOG_FILE="/tmp/express-cloud-stabilization-$(date -u +%Y%m%dT%H%M%SZ).log"
SKIP_PUSH="${SKIP_PUSH:-0}"
VERIFY_LEVEL="${VERIFY_LEVEL:-full}"

exec > >(tee -a "$LOG_FILE") 2>&1

fail() {
    local code=$?
    echo
    echo "============================================================"
    echo "EXPRESS CLOUD STABILIZATION FAILED"
    echo "Line: ${1:-unknown}"
    echo "Exit code: $code"
    echo "Log retained at: $LOG_FILE"
    echo "============================================================"
    exit "$code"
}
trap 'fail "$LINENO"' ERR

section() {
    echo
    echo "============================================================"
    echo "$1"
    echo "============================================================"
}

assert_file() { [[ -s "$1" ]] || { echo "Missing required file: $1"; exit 1; }; }

section "Express Cloud stabilization preflight"
[[ -d .git && -f artisan ]] || { echo "Run from the Express Cloud repository root."; exit 1; }

# Ignore this script itself, but refuse to overwrite unrelated tracked work.
tracked_changes="$(git status --porcelain --untracked-files=no)"
if [[ -n "$tracked_changes" ]]; then
    echo "Tracked files contain uncommitted work. Commit or stash them first."
    printf '%s\n' "$tracked_changes"
    exit 1
fi

for file in \
    app/Models/Account.php \
    app/Services/Organisation/AuthorizationService.php \
    app/Support/Authorization/PermissionCatalog.php \
    routes/admin.php \
    routes/staff.php \
    resources/views/components/navigation/sidebar.blade.php
do
    assert_file "$file"
done

mkdir -p \
    app/Http/Controllers/Staff \
    app/Http/Controllers/Admin/Insights \
    app/Services/Dashboard \
    app/Services/Insights \
    app/Console/Commands \
    resources/views/admin/insights \
    tests/Feature/Stabilization \
    tests/Unit/Authentication \
    tests/Unit/Insights \
    docs/features

section "Repairing alphabet-only access keys"
cat > app/Support/Security/LoginKeyGenerator.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Support\Security;

use InvalidArgumentException;

final class LoginKeyGenerator
{
    /** Ambiguous I, L and O are excluded. Access keys contain letters only. */
    public const string ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    public const int RAW_LENGTH = 8;

    public function generate(): string
    {
        $characters = [];

        for ($index = 0; $index < self::RAW_LENGTH; $index++) {
            $characters[] = self::ALPHABET[
                random_int(0, strlen(self::ALPHABET) - 1)
            ];
        }

        return self::format(implode('', $characters));
    }

    public static function normalize(string $value): string
    {
        $normalized = mb_strtoupper(trim($value));
        $normalized = str_replace(['-', ' '], '', $normalized);

        if (
            strlen($normalized) !== self::RAW_LENGTH
            || strspn($normalized, self::ALPHABET) !== self::RAW_LENGTH
        ) {
            throw new InvalidArgumentException(
                'Access key must contain exactly eight approved letters.',
            );
        }

        return $normalized;
    }

    public static function format(string $value): string
    {
        $normalized = self::normalize($value);

        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 4);
    }
}
PHP

cat > app/Http/Requests/Auth/LoginRequest.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'account_public_id' => ['required', 'uuid'],
            'access_key' => [
                'required',
                'string',
                'regex:/^[A-HJ-KM-NP-Z]{4}-?[A-HJ-KM-NP-Z]{4}$/i',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'account_public_id.required' => 'Select your staff name.',
            'access_key.required' => 'Enter your access key.',
            'access_key.regex' => 'Enter eight letters in XXXX-XXXX format.',
        ];
    }
}
PHP

# Replace old examples and browser patterns without depending on exact old markup.
python3 - <<'PY'
from pathlib import Path
p = Path('resources/views/auth/login.blade.php')
s = p.read_text()
s = s.replace('K7M4-P9XR', 'ECBV-YKQW')
s = s.replace('A-HJ-KM-NP-Z2-9', 'A-HJ-KM-NP-Z')
s = s.replace('approved characters', 'approved letters')
p.write_text(s)
PY

section "Fixing timestamp declarations and duplicate generated fragments"
python3 - <<'PY'
from pathlib import Path
import re
for p in Path('app').rglob('*.php'):
    s = p.read_text()
    s = re.sub(r"public const(?: string)? UPDATED_AT\s*=\s*'';", "public const UPDATED_AT = null;", s)
    # Remove repeated adjacent Account phpdoc lines caused by previous repair scripts.
    s = re.sub(r"(\s*/\*\* @var Account\|null \$account \*/\n)(?:\1)+", r"\1", s)
    p.write_text(s)
PY

section "Implementing permission authorization with concealed 404 responses"
cat > app/Http/Middleware/RequirePermission.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Account;
use App\Services\Organisation\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequirePermission
{
    public function __construct(
        private AuthorizationService $authorization,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $permission,
    ): Response {
        /** @var Account|null $account */
        $account = $request->user();

        abort_unless(
            $account !== null
            && $this->authorization->hasPermission($account, $permission),
            404,
        );

        return $next($request);
    }
}
PHP

cat > app/Services/Organisation/AuthorizationService.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Collection;

final class AuthorizationService
{
    /** @var array<string, array<string, bool>> */
    private array $permissionCache = [];

    public function hasPermission(Account $account, string $permission): bool
    {
        $accountId = (string) $account->getKey();

        if (isset($this->permissionCache[$accountId][$permission])) {
            return $this->permissionCache[$accountId][$permission];
        }

        $allowed = $account->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', static function ($query) use ($permission): void {
                $query->where('permissions.slug', $permission);
            })
            ->exists();

        return $this->permissionCache[$accountId][$permission] = $allowed;
    }

    /** @param list<string> $permissions */
    public function hasAnyPermission(Account $account, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($account, $permission)) {
                return true;
            }
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
            ->unique()
            ->values();
    }

    public function canAccessBranch(Account $account, Branch|string $branch): bool
    {
        if ($account->is_allowed_all_branches) {
            return true;
        }

        $branchId = $branch instanceof Branch
            ? (string) $branch->getKey()
            : $branch;

        return $account->branches()->whereKey($branchId)->exists();
    }
}
PHP

section "Registering complete permission-aware navigation"
cat > config/navigation.php <<'PHP'
<?php

declare(strict_types=1);

return [
    'primary' => [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Admin Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'permission' => 'dashboard.view'],
                ['label' => 'My Dashboard', 'icon' => 'gauge', 'route' => 'staff.dashboard', 'permission' => null],
                ['label' => 'Create Sale', 'icon' => 'shopping-cart', 'route' => 'admin.sales.create', 'permission' => 'sales.create'],
                ['label' => 'Sales & Quotes', 'icon' => 'receipt-text', 'route' => 'admin.sales.index', 'permission_any' => ['sales.view', 'sales.view.own', 'sales.view.all']],
                ['label' => 'Customers & Credit', 'icon' => 'users', 'route' => 'admin.customers.index', 'permission' => 'customers.view'],
                ['label' => 'Receivables', 'icon' => 'hand-coins', 'route' => 'admin.commercial.receivables.index', 'permission' => 'customers.receivables.view'],
            ],
        ],
        [
            'label' => 'Catalogue & Stock',
            'items' => [
                ['label' => 'Products', 'icon' => 'package', 'route' => 'admin.catalog.products.index', 'permission' => 'products.view'],
                ['label' => 'Product Import', 'icon' => 'file-up', 'route' => 'admin.imports.products.index', 'permission_any' => ['products.import', 'products.import-history']],
                ['label' => 'Inventory', 'icon' => 'warehouse', 'route' => 'admin.inventory.index', 'permission' => 'inventory.view'],
                ['label' => 'Stock Movements', 'icon' => 'arrow-left-right', 'route' => 'admin.inventory.movements', 'permission' => 'inventory.movements.view'],
                ['label' => 'Low Stock', 'icon' => 'triangle-alert', 'route' => 'admin.reports.low-stock', 'permission' => 'reports.low-stock'],
            ],
        ],
        [
            'label' => 'Purchasing',
            'items' => [
                ['label' => 'Suppliers', 'icon' => 'contact-round', 'route' => 'admin.catalog.suppliers.index', 'permission' => 'suppliers.view'],
                ['label' => 'Purchase Orders', 'icon' => 'truck', 'route' => 'admin.procurement.orders.index', 'permission' => 'procurement.view'],
                ['label' => 'Direct Purchases', 'icon' => 'package-plus', 'route' => 'admin.commercial.purchases.index', 'permission' => 'purchases.view'],
                ['label' => 'Supplier Bills', 'icon' => 'file-text', 'route' => 'admin.supplier-finance.bills.index', 'permission' => 'supplier-bills.view'],
                ['label' => 'Supplier Returns', 'icon' => 'undo-2', 'route' => 'admin.supplier-finance.returns.index', 'permission' => 'supplier-returns.view'],
            ],
        ],
        [
            'label' => 'Finance & Intelligence',
            'items' => [
                ['label' => 'Accounting', 'icon' => 'landmark', 'route' => 'admin.accounting.reports.index', 'permission' => 'accounting.reports.view'],
                ['label' => 'Fixed Assets', 'icon' => 'building-2', 'route' => 'admin.accounting-operations.assets.index', 'permission' => 'assets.view'],
                ['label' => 'Reports', 'icon' => 'chart-no-axes-combined', 'route' => 'admin.reports.hub', 'permission' => 'reports.hub.view'],
                ['label' => 'Lisa AI', 'icon' => 'bot-message-square', 'route' => 'admin.insights.index', 'permission' => 'insights.view'],
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                ['label' => 'Branches', 'icon' => 'map-pin-house', 'route' => 'admin.branches.index', 'permission' => 'branches.view'],
                ['label' => 'Staff', 'icon' => 'user-cog', 'route' => 'admin.staff.index', 'permission' => 'staff.view'],
                ['label' => 'Roles & Permissions', 'icon' => 'shield-check', 'route' => 'admin.roles.index', 'permission' => 'roles.view'],
                ['label' => 'Payment Methods', 'icon' => 'credit-card', 'route' => 'admin.payment-methods.index', 'permission' => 'payment-methods.view'],
                ['label' => 'Business Settings', 'icon' => 'settings', 'route' => 'admin.operations.settings.edit', 'permission' => 'settings.business.manage'],
                ['label' => 'Activity Log', 'icon' => 'history', 'route' => 'admin.activity.index', 'permission' => 'activity.view'],
                ['label' => 'Live Sessions', 'icon' => 'monitor-smartphone', 'route' => 'admin.security.sessions.index', 'permission' => 'security.sessions.view'],
                ['label' => 'API Tokens', 'icon' => 'key-round', 'route' => 'admin.api.tokens.index', 'permission' => 'api.tokens.manage'],
                ['label' => 'Backups', 'icon' => 'database-backup', 'route' => 'admin.operations.backups.index', 'permission' => 'backups.view'],
            ],
        ],
    ],
    'secondary' => [
        ['label' => 'Profile', 'icon' => 'circle-user-round', 'route' => 'staff.profile.show', 'permission' => null],
    ],
];
PHP

cat > resources/views/components/navigation/sidebar.blade.php <<'BLADE'
@php
    /** @var \App\Models\Account|null $navigationAccount */
    $navigationAccount = auth()->user();
    $authorization = app(\App\Services\Organisation\AuthorizationService::class);

    $canSee = static function (array $item) use ($navigationAccount, $authorization): bool {
        if ($navigationAccount === null) {
            return false;
        }

        if (!empty($item['permission'])) {
            return $authorization->hasPermission($navigationAccount, $item['permission']);
        }

        if (!empty($item['permission_any'])) {
            return $authorization->hasAnyPermission($navigationAccount, $item['permission_any']);
        }

        return true;
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 hidden border-r border-white/10 bg-[var(--ec-navy-900)] text-white transition-[width] duration-200 lg:flex lg:flex-col"
    :class="$store.shell.sidebarCollapsed ? 'w-[72px]' : 'w-[280px]'"
>
    <div class="flex h-16 items-center border-b border-white/10 px-4">
        <a href="{{ route('staff.dashboard') }}" class="flex min-w-0 items-center gap-3">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-[var(--ec-navy-900)]"><span class="text-sm font-bold">EC</span></div>
            <div x-show="!$store.shell.sidebarCollapsed" x-transition.opacity.duration.150ms class="min-w-0">
                <p class="truncate text-sm font-semibold">Express Cloud</p>
                <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400">by Zivora</p>
            </div>
        </a>
    </div>

    <nav class="ec-scrollbar flex-1 overflow-y-auto px-3 py-4" aria-label="Primary navigation">
        @foreach (config('navigation.primary', []) as $section)
            @php $visibleItems = collect($section['items'])->filter($canSee); @endphp
            @if ($visibleItems->isNotEmpty())
                <section class="mb-5">
                    <p x-show="!$store.shell.sidebarCollapsed" class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $section['label'] }}</p>
                    <div class="space-y-1">
                        @foreach ($visibleItems as $item)
                            <x-navigation.sidebar-link
                                :label="$item['label']"
                                :icon="$item['icon']"
                                :href="route($item['route'])"
                                :active="request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']))"
                            />
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        <div class="space-y-1">
            @foreach (collect(config('navigation.secondary', []))->filter($canSee) as $item)
                <x-navigation.sidebar-link :label="$item['label']" :icon="$item['icon']" :href="route($item['route'])" :active="request()->routeIs($item['route'])" />
            @endforeach
        </div>
        <button type="button" class="mt-3 flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium text-slate-300 hover:bg-white/8 hover:text-white" @click="$store.shell.toggleSidebar()">
            <x-ui.icon name="panel-left-close" :size="19" x-show="!$store.shell.sidebarCollapsed" />
            <x-ui.icon name="panel-left-open" :size="19" x-show="$store.shell.sidebarCollapsed" />
            <span x-show="!$store.shell.sidebarCollapsed">Collapse</span>
        </button>
    </div>
</aside>
BLADE

# Ensure sidebar link supports real hrefs.
cat > resources/views/components/navigation/sidebar-link.blade.php <<'BLADE'
@props([
    'label',
    'icon',
    'href' => '#',
    'active' => false,
])
<a
    href="{{ $href }}"
    @class([
        'group flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium transition',
        'bg-white/12 text-white' => $active,
        'text-slate-300 hover:bg-white/8 hover:text-white' => !$active,
    ])
    @if($active) aria-current="page" @endif
>
    <x-ui.icon :name="$icon" :size="19" />
    <span x-show="!$store.shell.sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">{{ $label }}</span>
</a>
BLADE

section "Repairing dashboard reports and creating a real staff dashboard"
cat > app/Services/Reports/StaffPerformanceReport.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StaffPerformanceReport
{
    /** @return Collection<int, \stdClass> */
    public function run(string $from, string $to, ?string $branchId): Collection
    {
        $sales = DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled'])
            ->when($branchId !== null, static fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('sold_by_account_id')
            ->selectRaw('sold_by_account_id AS account_id')
            ->selectRaw('COUNT(*) AS sales_count')
            ->selectRaw('COALESCE(SUM(grand_total_kobo), 0) AS revenue_kobo')
            ->selectRaw('COUNT(DISTINCT customer_id) AS customers_served');

        $units = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->whereNotIn('sales.status', ['cancelled'])
            ->when($branchId !== null, static fn ($query) => $query->where('sales.branch_id', $branchId))
            ->groupBy('sales.sold_by_account_id')
            ->selectRaw('sales.sold_by_account_id AS account_id')
            ->selectRaw('COALESCE(SUM(sale_items.quantity_milliunits), 0) AS units_milliunits');

        return DB::table('accounts')
            ->joinSub($sales, 'staff_sales', static fn ($join) => $join->on('staff_sales.account_id', '=', 'accounts.id'))
            ->leftJoinSub($units, 'staff_units', static fn ($join) => $join->on('staff_units.account_id', '=', 'accounts.id'))
            ->where('accounts.status', 'active')
            ->orderByDesc('staff_sales.revenue_kobo')
            ->select([
                'accounts.id AS account_id',
                'accounts.first_name',
                'accounts.last_name',
                'staff_sales.sales_count',
                'staff_sales.revenue_kobo',
                'staff_sales.customers_served',
            ])
            ->selectRaw('COALESCE(staff_units.units_milliunits, 0) AS units_milliunits')
            ->get();
    }
}
PHP

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
            ->get(['id', 'reference', 'sale_type', 'status', 'sale_date', 'grand_total_kobo', 'balance_due_kobo']);

        $branchIds = $account->is_allowed_all_branches
            ? []
            : $account->branches()->pluck('branches.id')->all();

        $lowStockCount = 0;
        if ($this->authorization->hasAnyPermission($account, ['inventory.view', 'reports.low-stock'])) {
            $stock = DB::table('product_branch_stocks')
                ->whereColumn('quantity_milliunits', '<=', 'reorder_level_milliunits');
            if (!$account->is_allowed_all_branches) {
                $stock->whereIn('branch_id', $branchIds);
            }
            $lowStockCount = $stock->count();
        }

        return [
            'todaySalesCount' => (clone $sales)->whereDate('sale_date', $today)->count(),
            'todayRevenueKobo' => (int) (clone $sales)->whereDate('sale_date', $today)->sum('grand_total_kobo'),
            'monthRevenueKobo' => (int) (clone $sales)->whereBetween('sale_date', [$monthStart, $today])->sum('grand_total_kobo'),
            'outstandingKobo' => (int) (clone $sales)->where('balance_due_kobo', '>', 0)->sum('balance_due_kobo'),
            'recentSales' => $recentSales,
            'lowStockCount' => $lowStockCount,
            'permissions' => $this->authorization->permissionSlugs($account),
        ];
    }
}
PHP

cat > app/Http/Controllers/Staff/StaffDashboardController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Models\Account;
use App\Services\Dashboard\StaffDashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class StaffDashboardController
{
    public function __construct(private StaffDashboardData $dashboard) {}

    public function __invoke(Request $request): View
    {
        /** @var Account $account */
        $account = $request->user();

        return view('staff.dashboard', [
            'account' => $account,
            ...$this->dashboard->for($account),
        ]);
    }
}
PHP

cat > resources/views/staff/dashboard.blade.php <<'BLADE'
<x-layout.app>
    <x-layout.app-shell page-title="My workspace" page-description="Your permission-scoped operational dashboard.">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sales today</p><p class="mt-2 text-2xl font-bold">{{ number_format($todaySalesCount) }}</p></x-ui.card>
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue today</p><p class="mt-2 text-2xl font-bold">₦{{ number_format($todayRevenueKobo / 100, 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revenue this month</p><p class="mt-2 text-2xl font-bold">₦{{ number_format($monthRevenueKobo / 100, 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding on my sales</p><p class="mt-2 text-2xl font-bold">₦{{ number_format($outstandingKobo / 100, 2) }}</p></x-ui.card>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.5fr_1fr]">
            <x-ui.card>
                <div class="flex items-center justify-between"><div><h2 class="font-semibold">Recent activity</h2><p class="text-sm text-slate-500">Invoices, POS sales and quotes created by you.</p></div></div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-3">Reference</th><th>Type</th><th>Status</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Due</th></tr></thead>
                        <tbody class="divide-y">
                            @forelse($recentSales as $sale)
                                <tr><td class="py-3 font-medium">{{ $sale->reference }}</td><td>{{ ucfirst($sale->sale_type) }}</td><td>{{ ucfirst($sale->status) }}</td><td>{{ $sale->sale_date }}</td><td class="text-right">₦{{ number_format(((int) $sale->grand_total_kobo) / 100, 2) }}</td><td class="text-right">₦{{ number_format(((int) $sale->balance_due_kobo) / 100, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-slate-500">No sales activity yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="space-y-5">
                <x-ui.card>
                    <h2 class="font-semibold">Quick actions</h2>
                    <div class="mt-4 grid gap-2">
                        @if($permissions->contains('sales.create'))<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.sales.create') }}">Create sale or invoice</a>@endif
                        @if($permissions->intersect(['sales.view','sales.view.own','sales.view.all'])->isNotEmpty())<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.sales.index') }}">View sales</a>@endif
                        @if($permissions->contains('customers.view'))<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.customers.index') }}">Customers</a>@endif
                        @if($permissions->contains('inventory.view'))<a class="rounded-lg border px-3 py-2 text-sm font-medium hover:border-slate-400" href="{{ route('admin.inventory.index') }}">Inventory</a>@endif
                    </div>
                </x-ui.card>
                @if($permissions->intersect(['inventory.view','reports.low-stock'])->isNotEmpty())
                    <x-ui.card><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Low-stock items in permitted branches</p><p class="mt-2 text-3xl font-bold">{{ number_format($lowStockCount) }}</p></x-ui.card>
                @endif
            </div>
        </div>
    </x-layout.app-shell>
</x-layout.app>
BLADE

section "Correcting post-login destination"
python3 - <<'PY'
from pathlib import Path
p = Path('app/Http/Controllers/Auth/AuthenticatedSessionController.php')
s = p.read_text()
s = s.replace("        return redirect()->intended(route('staff.dashboard'));", """        $destination = app(\\App\\Services\\Organisation\\AuthorizationService::class)
            ->hasPermission($account, 'dashboard.view')
                ? route('admin.dashboard')
                : route('staff.dashboard');

        return redirect()->intended($destination);""")
# Canonicalize duplicate doc comments left by earlier generated repairs.
lines = s.splitlines()
out=[]
for line in lines:
    if out and line == out[-1] and '@var Account|null $account' in line:
        continue
    out.append(line)
p.write_text('\n'.join(out)+'\n')
PY

python3 - <<'PY'
from pathlib import Path
p = Path('routes/staff.php')
s = p.read_text()
imp = "use App\\Http\\Controllers\\Staff\\StaffDashboardController;\n"
if imp not in s:
    marker = "use App\\Http\\Controllers\\Staff\\Commercial\\SaleReturnController;\n"
    s = s.replace(marker, marker + imp)
s = s.replace("    Route::view('/dashboard', 'staff.dashboard')\n        ->name('dashboard');", "    Route::get('/dashboard', StaffDashboardController::class)\n        ->name('dashboard');")
p.write_text(s)
PY

section "Implementing Lisa AI v2 as authorized server-side business intelligence"
cat > database/migrations/2026_07_16_001900_create_business_insights_table.php <<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_insights', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('category', 40)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('title', 160);
            $table->text('summary');
            $table->text('recommendation')->nullable();
            $table->json('evidence')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamp('generated_at')->index();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->unique(['category', 'branch_id', 'period_start', 'period_end', 'title'], 'business_insights_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_insights');
    }
};
PHP

cat > app/Models/BusinessInsight.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class BusinessInsight extends Model
{
    use HasUlids;

    protected $fillable = [
        'category', 'severity', 'title', 'summary', 'recommendation', 'evidence',
        'period_start', 'period_end', 'branch_id', 'generated_at', 'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'immutable_datetime',
            'dismissed_at' => 'immutable_datetime',
        ];
    }
}
PHP

cat > app/Services/Insights/LisaInsightEngine.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\BusinessInsight;
use Illuminate\Support\Facades\DB;

final class LisaInsightEngine
{
    public function generate(string $from, string $to): int
    {
        $generated = 0;

        $currentRevenue = (int) DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled'])
            ->sum('grand_total_kobo');

        $discounts = (int) DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->sum('discount_total_kobo');

        if ($currentRevenue > 0 && $discounts > (int) round($currentRevenue * 0.08)) {
            $generated += $this->store([
                'category' => 'sales',
                'severity' => 'warning',
                'title' => 'Discount pressure is reducing revenue quality',
                'summary' => 'Discounts exceeded 8% of recorded revenue during the selected period.',
                'recommendation' => 'Review high-discount sales, voucher usage and staff override patterns before approving additional discounts.',
                'evidence' => ['revenue_kobo' => $currentRevenue, 'discount_kobo' => $discounts],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        $lowStock = DB::table('product_branch_stocks')
            ->whereColumn('quantity_milliunits', '<=', 'reorder_level_milliunits')
            ->count();

        if ($lowStock > 0) {
            $generated += $this->store([
                'category' => 'inventory',
                'severity' => $lowStock >= 20 ? 'critical' : 'warning',
                'title' => 'Products require replenishment attention',
                'summary' => sprintf('%d branch-product records are at or below their reorder level.', $lowStock),
                'recommendation' => 'Open the low-stock report, prioritize fast-moving items and create purchase orders or stock transfers.',
                'evidence' => ['low_stock_records' => $lowStock],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        $outstanding = (int) DB::table('sales')
            ->where('balance_due_kobo', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->sum('balance_due_kobo');

        if ($outstanding > 0) {
            $generated += $this->store([
                'category' => 'customers',
                'severity' => 'info',
                'title' => 'Customer receivables require follow-up',
                'summary' => 'The business has unpaid or partially paid sales in its receivables ledger.',
                'recommendation' => 'Review customer balances and record settlements against the original sales as payments arrive.',
                'evidence' => ['outstanding_kobo' => $outstanding],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        if ($generated === 0) {
            $generated += $this->store([
                'category' => 'executive',
                'severity' => 'info',
                'title' => 'No material operational exception detected',
                'summary' => 'Lisa did not find a threshold breach in sales discounts, low stock or receivables for the selected period.',
                'recommendation' => 'Continue monitoring branch performance and review detailed reports for context.',
                'evidence' => [],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        return $generated;
    }

    /** @param array<string, mixed> $data */
    private function store(array $data): int
    {
        BusinessInsight::query()->updateOrCreate(
            [
                'category' => $data['category'],
                'branch_id' => $data['branch_id'] ?? null,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'title' => $data['title'],
            ],
            [...$data, 'generated_at' => now(), 'dismissed_at' => null],
        );

        return 1;
    }
}
PHP

cat > app/Http/Controllers/Admin/Insights/LisaInsightController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Insights;

use App\Models\BusinessInsight;
use App\Services\Insights\LisaInsightEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class LisaInsightController
{
    public function __construct(private LisaInsightEngine $engine) {}

    public function index(Request $request): View
    {
        $from = $request->date('from')?->toDateString() ?? now()->subDays(30)->toDateString();
        $to = $request->date('to')?->toDateString() ?? today()->toDateString();

        return view('admin.insights.index', [
            'from' => $from,
            'to' => $to,
            'insights' => BusinessInsight::query()
                ->whereBetween('period_end', [$from, $to])
                ->whereNull('dismissed_at')
                ->latest('generated_at')
                ->paginate(24)
                ->withQueryString(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $count = $this->engine->generate($validated['from'], $validated['to']);

        return back()->with('status', sprintf('Lisa refreshed %d business insights.', $count));
    }

    public function dismiss(BusinessInsight $insight): RedirectResponse
    {
        $insight->forceFill(['dismissed_at' => now()])->save();

        return back()->with('status', 'Insight dismissed.');
    }
}
PHP

cat > app/Console/Commands/GenerateLisaInsights.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Insights\LisaInsightEngine;
use Illuminate\Console\Command;

final class GenerateLisaInsights extends Command
{
    protected $signature = 'lisa:generate {--from=} {--to=}';
    protected $description = 'Generate permission-safe business insights from summarized operational data.';

    public function handle(LisaInsightEngine $engine): int
    {
        $from = (string) ($this->option('from') ?: now()->subDays(30)->toDateString());
        $to = (string) ($this->option('to') ?: today()->toDateString());
        $count = $engine->generate($from, $to);
        $this->info(sprintf('Generated or refreshed %d Lisa insights.', $count));

        return self::SUCCESS;
    }
}
PHP

cat > resources/views/admin/insights/index.blade.php <<'BLADE'
<x-layout.app>
    <x-layout.app-shell page-title="Lisa AI" page-description="Permission-safe business analysis generated from summarized operational records. Lisa does not execute arbitrary SQL or receive access keys and credentials.">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.insights.generate') }}" class="flex flex-wrap items-end gap-2">@csrf
                <label class="text-xs text-slate-500">From<input class="ml-2 rounded-lg border px-3 py-2" type="date" name="from" value="{{ $from }}" required></label>
                <label class="text-xs text-slate-500">To<input class="ml-2 rounded-lg border px-3 py-2" type="date" name="to" value="{{ $to }}" required></label>
                <x-ui.button type="submit">Refresh insights</x-ui.button>
            </form>
        </x-slot:actions>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($insights as $insight)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $insight->category }} · {{ $insight->severity }}</p><h2 class="mt-1 text-lg font-semibold">{{ $insight->title }}</h2></div>
                        <form method="POST" action="{{ route('admin.insights.dismiss', $insight) }}">@csrf @method('PATCH')<button class="text-xs font-medium text-slate-500 hover:text-slate-900">Dismiss</button></form>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $insight->summary }}</p>
                    @if($insight->recommendation)<div class="mt-4 rounded-lg bg-slate-50 p-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recommended action</p><p class="mt-1 text-sm text-slate-700">{{ $insight->recommendation }}</p></div>@endif
                    <p class="mt-3 text-xs text-slate-400">Generated {{ $insight->generated_at?->diffForHumans() }} · {{ $insight->period_start?->toDateString() }} to {{ $insight->period_end?->toDateString() }}</p>
                </x-ui.card>
            @empty
                <x-ui.card><p class="text-sm text-slate-500">No insight has been generated for this period. Use Refresh insights.</p></x-ui.card>
            @endforelse
        </div>
        <div class="mt-5">{{ $insights->links() }}</div>
    </x-layout.app-shell>
</x-layout.app>
BLADE

section "Extending permissions and routes safely"
python3 - <<'PY'
from pathlib import Path
p = Path('app/Support/Authorization/PermissionCatalog.php')
s = p.read_text()
if "'insights.view'" not in s:
    needle = "                'accounting.depreciation.post' => 'Post fixed-asset depreciation',\n"
    add = needle + "                'insights.view' => 'View Lisa AI business insights',\n                'insights.generate' => 'Generate Lisa AI business insights',\n                'insights.dismiss' => 'Dismiss Lisa AI business insights',\n"
    if needle not in s:
        raise SystemExit('Permission catalogue accounting marker missing.')
    s = s.replace(needle, add, 1)
p.write_text(s)

p = Path('routes/admin.php')
s = p.read_text()
imp = "use App\\Http\\Controllers\\Admin\\Insights\\LisaInsightController;\n"
if imp not in s:
    pos = s.find('use ')
    s = s[:pos] + imp + s[pos:]
if "admin.insights.index" not in s:
    block = """
    Route::get('/insights', [LisaInsightController::class, 'index'])
        ->middleware('permission:insights.view')
        ->name('insights.index');
    Route::post('/insights/generate', [LisaInsightController::class, 'generate'])
        ->middleware('permission:insights.generate')
        ->name('insights.generate');
    Route::patch('/insights/{insight}/dismiss', [LisaInsightController::class, 'dismiss'])
        ->middleware('permission:insights.dismiss')
        ->name('insights.dismiss');
"""
    close = s.rfind('\n});')
    if close < 0:
        raise SystemExit('Admin route group closing marker missing.')
    s = s[:close] + block + s[close:]
p.write_text(s)
PY

section "Removing stale production placeholders"
rm -f resources/views/auth/login-placeholder.blade.php
python3 - <<'PY'
from pathlib import Path
replacements = {
    'Operational asset register. Ledger postings and depreciation journals arrive in Sprint 19.': 'Operational fixed-asset register with depreciation and accounting controls.',
    'Operational modules are introduced in later sprints.': 'Your permission-scoped operational workspace.',
}
for p in Path('resources/views').rglob('*.blade.php'):
    s = p.read_text()
    for old, new in replacements.items():
        s = s.replace(old, new)
    p.write_text(s)
PY

section "Adding stabilization regression tests"
cat > tests/Unit/Authentication/AlphabetOnlyLoginKeyTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Support\Security\LoginKeyGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AlphabetOnlyLoginKeyTest extends TestCase
{
    public function test_generated_keys_are_alphabetic_and_grouped(): void
    {
        $key = (new LoginKeyGenerator())->generate();
        self::assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z]{4}-[A-HJ-KM-NP-Z]{4}$/', $key);
    }

    public function test_normalization_removes_the_hyphen(): void
    {
        self::assertSame('ECBVYKQW', LoginKeyGenerator::normalize('ECBV-YKQW'));
    }

    public function test_numbers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LoginKeyGenerator::normalize('EC19-ABCD');
    }
}
PHP

cat > tests/Feature/Stabilization/PermissionConcealmentTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Feature\Stabilization;

use App\Http\Middleware\RequirePermission;
use App\Models\Account;
use App\Services\Organisation\AuthorizationService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class PermissionConcealmentTest extends TestCase
{
    #[Test]
    public function denied_permissions_are_concealed_as_not_found(): void
    {
        $account = new Account();
        $request = Request::create('/admin/example');
        $request->setUserResolver(static fn () => $account);

        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->method('hasPermission')->willReturn(false);

        $middleware = new RequirePermission($authorization);
        $this->expectException(NotFoundHttpException::class);
        $middleware->handle($request, static fn () => response('ok'), 'example.view');
    }
}
PHP

cat > tests/Unit/Insights/LisaInsightArchitectureTest.php <<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Unit\Insights;

use PHPUnit\Framework\TestCase;

final class LisaInsightArchitectureTest extends TestCase
{
    public function test_lisa_does_not_contain_arbitrary_sql_or_credentials(): void
    {
        $source = file_get_contents(app_path('Services/Insights/LisaInsightEngine.php'));
        self::assertIsString($source);
        self::assertStringNotContainsString('DB::unprepared', $source);
        self::assertStringNotContainsString('login_key', $source);
        self::assertStringNotContainsString('APP_KEY', $source);
    }
}
PHP

cat > docs/features/stabilization-and-lisa-ai.md <<'MD'
# Application Stabilization and Lisa AI

This stabilization release connects the modules already implemented across the project into a coherent, permission-aware application.

## Authorization

Protected routes use named permissions and return 404 to authenticated users who lack access. The desktop and mobile navigation are generated from the same permission rules, so hidden links and direct URL protection remain aligned. Staff dashboards show the authenticated account's operational data; company-wide dashboards remain permission-gated.

## Access keys

Access keys are eight alphabetic characters displayed as `XXXX-XXXX`. Ambiguous letters I, L and O are excluded. The encrypted value and blind index are derived from the same normalized eight-letter value. Plaintext keys are displayed only when generated or rotated.

## Lisa AI v2

Lisa is an internal server-side business insight module, not an unrestricted chatbot. It operates on summarized, already-authorized operational data and stores explainable insights containing a category, severity, summary, recommendation and evidence. It never generates arbitrary production SQL and never receives login keys, application keys or encrypted credentials.

Current detectors cover discount pressure, low-stock exposure, receivables and a safe no-exception executive summary. The command `php artisan lisa:generate` can be scheduled; authorized users can also refresh insights from the Lisa interface.

## Database consistency

The application no longer queries the removed `accounts.account_type` column. Staff performance uses independent per-sale and per-line aggregates to avoid multiplying revenue when a sale contains multiple items. The new `business_insights` table is defined only through a migration; SQL packaging is intentionally deferred.
MD

section "Static syntax verification"
find app config database routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/tmp/express-cloud-php-lint.log
cat /tmp/express-cloud-php-lint.log
php artisan route:list >/tmp/express-cloud-routes.txt

grep -q 'admin/insights' /tmp/express-cloud-routes.txt
grep -q 'staff/dashboard' /tmp/express-cloud-routes.txt

if grep -RInE --exclude-dir=vendor --exclude-dir=node_modules \
    '(Operational modules are introduced in later sprints|arrive in Sprint 19|accounts\.account_type|public const( string)? UPDATED_AT = '\''\'\'';)' \
    app resources routes config database; then
    echo "A known stale or schema-invalid fragment remains."
    exit 1
fi

# Detect duplicate imports and duplicate method declarations in application PHP.
python3 - <<'PY'
from pathlib import Path
import re
errors=[]
for p in [*Path('app').rglob('*.php'), *Path('routes').rglob('*.php'), *Path('config').rglob('*.php')]:
    s=p.read_text()
    imports=re.findall(r'^use\s+([^;]+);$', s, re.M)
    dup=sorted({x for x in imports if imports.count(x)>1})
    if dup: errors.append(f'{p}: duplicate imports: {dup}')
    methods=re.findall(r'public\s+function\s+(\w+)\s*\(', s)
    dmethods=sorted({x for x in methods if methods.count(x)>1})
    if dmethods: errors.append(f'{p}: duplicate public methods: {dmethods}')
if errors:
    raise SystemExit('\n'.join(errors))
PY

if [[ "$VERIFY_LEVEL" == "full" ]]; then
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

    section "Fresh migration and seed verification"
    original_env="$(mktemp)"
    cp .env "$original_env"
    cleanup_env() { cp "$original_env" .env; rm -f "$original_env" database/stabilization.sqlite; }
    trap cleanup_env EXIT
    touch database/stabilization.sqlite
    DB_CONNECTION=sqlite DB_DATABASE="$(pwd)/database/stabilization.sqlite" php artisan migrate:fresh --seed --force
    DB_CONNECTION=sqlite DB_DATABASE="$(pwd)/database/stabilization.sqlite" php artisan lisa:generate
    cleanup_env
    trap - EXIT
fi

section "Final consistency checks"
git diff --check
php artisan route:list --path=admin/dashboard
php artisan route:list --path=staff/dashboard
php artisan route:list --path=admin/inventory
php artisan route:list --path=admin/accounting
php artisan route:list --path=admin/insights

# Do not package, dump SQL or change the release version in this sprint.
if git diff --name-only | grep -Eq '(^|/)(release|dist)/|\.zip$|express-cloud-install\.sql$'; then
    echo "Packaging or SQL output was created unexpectedly."
    exit 1
fi

section "Committing stabilization"
git add -- \
    app config database/migrations resources/views routes tests docs/features/stabilization-and-lisa-ai.md

if git diff --cached --quiet; then
    echo "No changes to commit. Stabilization may already be applied."
else
    git commit -m "fix(stabilization): complete dashboards authorization navigation and Lisa AI"
fi

if [[ "$SKIP_PUSH" != "1" ]]; then
    git push -u origin "$(git branch --show-current)"
else
    echo "SKIP_PUSH=1; push skipped."
fi

section "Stabilization cleanup"
rm -f \
    express-cloud-sprint-14-*.sh \
    express-cloud-sprint-15-*.sh \
    express-cloud-sprints-16-17-*.sh \
    express-cloud-sprint-19.sh \
    engineering-audit-report.txt
rm -rf .sprint-logs

# The running script is intentionally removed only after all verification and commit steps pass.
rm -f -- "$SCRIPT_PATH"

echo
echo "============================================================"
echo "EXPRESS CLOUD STABILIZATION COMPLETED"
echo "============================================================"
echo "No SQL dump was generated. No ZIP was packaged. Version remains unchanged."
echo "Log: $LOG_FILE"
git log --oneline -5
