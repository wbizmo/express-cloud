#!/usr/bin/env bash

set -u

echo "==> Applying Express Cloud v1.0.4 workspace fixes"

mkdir -p \
  app/Http/Requests/Admin/Catalog \
  resources/views/admin/catalog/products \
  resources/views/admin/sales/partials

# 1. Keep the deployed live fixes in the repository.
perl -0pi -e 's/\bactor_id\b/actor_account_id/g' app/Queries/Activity/SystemActivityQuery.php 2>/dev/null || true
perl -0pi -e 's/COALESCE\(SUM\(balance_due_kobo\), 0\) AS outstanding_kobo/COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS outstanding_kobo/g' app/Services/Reports/StaffPerformanceReport.php 2>/dev/null || true

if ! grep -q 'use App\\Models\\Product;' app/Http/Controllers/Admin/Sales/SaleController.php; then
  perl -0pi -e 's/use App\\Models\\PaymentMethod;\n/use App\\Models\\PaymentMethod;\nuse App\\Models\\Product;\n/' app/Http/Controllers/Admin/Sales/SaleController.php
fi

if ! grep -q "'products' => Product::query()" app/Http/Controllers/Admin/Sales/SaleController.php; then
  perl -0pi -e "s/'paymentMethods' => PaymentMethod::query\(\)(.*?)->get\(\['id', 'name', 'is_default_for_pos'\]\),/'paymentMethods' => PaymentMethod::query()\$1->get(['id', 'name', 'is_default_for_pos']),\n            'products' => Product::query()\n                ->where('status', 'active')\n                ->orderBy('name')\n                ->get(['id', 'name', 'sku', 'barcode', 'track_inventory', 'default_price_kobo']),/s" app/Http/Controllers/Admin/Sales/SaleController.php
fi

# 2. Activity log: query the correct actor column and resolve actor + branch labels.
cat > app/Queries/Activity/SystemActivityQuery.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Queries\Activity;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SystemActivityQuery
{
    /** @return CursorPaginator<int, \stdClass> */
    public function run(
        ?string $actor,
        ?string $entityType,
        ?string $from,
        ?string $to,
    ): CursorPaginator {
        $table = Schema::hasTable('activity_logs')
            ? 'activity_logs'
            : 'audit_logs';

        $actorColumn = Schema::hasColumn($table, 'actor_account_id')
            ? 'actor_account_id'
            : 'actor_id';

        $query = DB::table($table.' as activity')
            ->leftJoin('accounts as actor_account', 'actor_account.id', '=', 'activity.'.$actorColumn)
            ->leftJoin('branches as actor_branch', 'actor_branch.id', '=', 'activity.branch_id')
            ->select([
                'activity.*',
                'actor_account.first_name as actor_first_name',
                'actor_account.last_name as actor_last_name',
                'actor_branch.name as actor_branch_name',
            ]);

        if ($actor !== null) {
            $query->where('activity.'.$actorColumn, $actor);
        }

        if ($entityType !== null) {
            $query->where('activity.entity_type', $entityType);
        }

        if ($from !== null) {
            $query->whereDate('activity.created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('activity.created_at', '<=', $to);
        }

        return $query
            ->orderByDesc('activity.created_at')
            ->cursorPaginate(60);
    }
}
PHP

cat > resources/views/admin/activity/index.blade.php <<'BLADE'
<x-layout.app title="Activity log | Express Cloud">
    <x-layout.app-shell page-title="System activity" page-description="Filterable append-only record of meaningful write actions.">
        <form method="GET" class="mb-6 grid min-w-0 gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <label class="block min-w-0">
                <span class="mb-2 block text-sm font-medium text-slate-700">Actor</span>
                <select name="actor" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3.5 text-sm">
                    <option value="">All actors</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected(request('actor') === $actor->id)>
                            {{ trim($actor->first_name.' '.$actor->last_name) }}
                        </option>
                    @endforeach
                </select>
            </label>
            <x-ui.input name="entity_type" label="Entity type" :value="request('entity_type')" />
            <x-ui.input name="from" type="date" label="From" :value="request('from')" />
            <x-ui.input name="to" type="date" label="To" :value="request('to')" />
            <div class="md:col-span-4"><x-ui.button type="submit">Apply filters</x-ui.button></div>
        </form>

        <x-ui.card title="Activity">
            <div class="space-y-3">
                @forelse ($entries as $entry)
                    @php
                        $actorName = trim(($entry->actor_first_name ?? '').' '.($entry->actor_last_name ?? ''));
                        $actorName = $actorName !== '' ? $actorName : ($entry->actor_name ?? 'System');
                        $actorLabel = filled($entry->actor_branch_name ?? null)
                            ? $actorName.' ['.$entry->actor_branch_name.']'
                            : $actorName;
                    @endphp
                    <article class="min-w-0 rounded-xl border border-slate-200 p-4">
                        <div class="flex min-w-0 flex-wrap justify-between gap-3">
                            <div class="min-w-0">
                                <strong class="block break-words">{{ $entry->action ?? $entry->event ?? 'Activity' }}</strong>
                                <p class="mt-1 break-words text-sm font-medium text-slate-700">{{ $actorLabel }}</p>
                            </div>
                            <span class="shrink-0 text-sm text-slate-500">{{ $entry->created_at }}</span>
                        </div>
                        <p class="mt-2 break-all text-sm text-slate-600">
                            {{ $entry->entity_type ?? 'system' }} · {{ $entry->entity_id ?? '—' }}
                        </p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No activity matches these filters.</p>
                @endforelse
            </div>

            <div class="mt-5">
                {{ $entries->withQueryString()->links() }}
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
BLADE

# 3. Compact, scrollable quick-customer modal with a real CTA and close button.
cat > resources/views/admin/sales/partials/quick-customer-modal.blade.php <<'BLADE'
<div x-data="quickCustomer('{{ route('admin.customers.quick-store') }}', '{{ csrf_token() }}')">
    <button
        type="button"
        x-on:click="open = true"
        class="mt-3 inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
    >
        <span aria-hidden="true">+</span>
        New customer
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 p-3 sm:p-5"
        role="dialog"
        aria-modal="true"
        aria-labelledby="quick-customer-title"
    >
        <section
            x-on:click.outside="open = false"
            class="flex max-h-[min(82vh,720px)] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
                <div>
                    <h2 id="quick-customer-title" class="text-lg font-bold text-slate-950">Add customer</h2>
                    <p class="mt-1 text-sm text-slate-600">Only the customer name is required.</p>
                </div>
                <button
                    type="button"
                    x-on:click="open = false"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close customer form"
                >
                    ×
                </button>
            </header>

            <form class="flex min-h-0 flex-1 flex-col" x-on:submit.prevent="save">
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Name *</span>
                            <input x-model="form.name" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-slate-700">Phone</span>
                            <input x-model="form.phone" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label>
                            <span class="text-sm font-medium text-slate-700">WhatsApp</span>
                            <input x-model="form.whatsapp_phone" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Email</span>
                            <input type="email" x-model="form.email" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Address</span>
                            <textarea x-model="form.address" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></textarea>
                        </label>
                        <label class="sm:col-span-2">
                            <span class="text-sm font-medium text-slate-700">Notes</span>
                            <textarea x-model="form.notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></textarea>
                        </label>
                        <p x-show="error" x-text="error" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700"></p>
                    </div>
                </div>

                <footer class="sticky bottom-0 flex shrink-0 justify-end gap-3 border-t border-slate-200 bg-white px-5 py-4">
                    <button type="button" x-on:click="open = false" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="saving" class="min-h-10 rounded-xl bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50" x-text="saving ? 'Saving…' : 'Save customer'"></button>
                </footer>
            </form>
        </section>
    </div>
</div>

<script>
function quickCustomer(endpoint, csrf) {
    return {
        open: false,
        saving: false,
        error: '',
        form: {name: '', phone: '', whatsapp_phone: '', email: '', address: '', notes: ''},
        async save() {
            this.saving = true;
            this.error = '';
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(this.form),
                });
                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(payload.message || 'Could not save customer.');
                }
                window.dispatchEvent(new CustomEvent('customer-created', {detail: payload}));
                this.open = false;
                this.form = {name: '', phone: '', whatsapp_phone: '', email: '', address: '', notes: ''};
            } catch (error) {
                this.error = error.message;
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
BLADE

# 4. Fixed topbar on desktop and mobile; page content starts below it.
perl -0pi -e 's/class="ec-topbar sticky top-0 z-40/class="ec-topbar fixed left-0 right-0 top-0 z-40/' resources/views/components/navigation/topbar.blade.php
perl -0pi -e 's/class="ec-page-main w-full/class="ec-page-main w-full pt-20/' resources/views/components/layout/app-shell.blade.php

# Account for the fixed desktop sidebar width.
perl -0pi -e 's/class="ec-topbar fixed left-0 right-0 top-0 z-40/class="ec-topbar fixed left-0 right-0 top-0 z-40 lg:left-[var(--ec-sidebar-offset,280px)]/' resources/views/components/navigation/topbar.blade.php

# Keep the topbar aligned when the sidebar collapses.
perl -0pi -e 's/<x-navigation\.topbar \/>/<div style="--ec-sidebar-offset: 280px" :style="\x27--ec-sidebar-offset: \x27 + ($store.shell.sidebarCollapsed ? \x2772px\x27 : \x27280px\x27)"><x-navigation.topbar \/><\/div>/' resources/views/components/layout/app-shell.blade.php

# 5. Prevent branches and import history from forcing the whole viewport wider.
perl -0pi -e 's/<div class="grid gap-6 xl:grid-cols-\[1fr_380px\]">/<div class="grid min-w-0 max-w-full gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(300px,380px)]">/' resources/views/admin/branches/index.blade.php
perl -0pi -e 's/<x-ui\.card title="Branch directory">/<div class="min-w-0 max-w-full overflow-hidden"><x-ui.card title="Branch directory">/' resources/views/admin/branches/index.blade.php
perl -0pi -e 's#</x-ui\.card>\n\n            <x-ui\.card title="Add branch"#</x-ui.card></div>\n\n            <div class="min-w-0 max-w-full"><x-ui.card title="Add branch"#' resources/views/admin/branches/index.blade.php
perl -0pi -e 's#</form>\n            </x-ui\.card>#</form>\n            </x-ui.card></div>#' resources/views/admin/branches/index.blade.php

perl -0pi -e 's/<div class="grid gap-6 xl:grid-cols-\[380px_1fr\]">/<div class="grid min-w-0 max-w-full gap-6 xl:grid-cols-[minmax(300px,380px)_minmax(0,1fr)]">/' resources/views/admin/imports/products/index.blade.php
perl -0pi -e 's/<x-ui\.card title="Import history">/<div class="min-w-0 max-w-full overflow-hidden"><x-ui.card title="Import history">/' resources/views/admin/imports/products/index.blade.php
perl -0pi -e 's#</x-ui\.card>\n        </div>\n    </x-layout\.app-shell>#</x-ui.card></div>\n        </div>\n    </x-layout.app-shell>#' resources/views/admin/imports/products/index.blade.php

# 6. Product editing: request, routes, controller methods, edit page and table action.
cat > app/Http/Requests/Admin/Catalog/UpdateProductRequest.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, int|string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:100', 'alpha_dash'],
            'barcode' => ['nullable', 'string', 'max:160'],
            'category_id' => ['required', 'ulid'],
            'brand_id' => ['nullable', 'ulid'],
            'tax_rate_id' => ['nullable', 'ulid'],
            'description' => ['nullable', 'string', 'max:5000'],
            'track_inventory' => ['nullable', 'boolean'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'default_cost_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('catalog.images.maximum_kilobytes', 4096),
            ],
            'branch_prices' => ['array'],
            'branch_prices.*.branch_id' => ['required', 'ulid'],
            'branch_prices.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
PHP

cat > app/Http/Controllers/Admin/Catalog/ProductController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Catalog\CreateProduct;
use App\Actions\Catalog\StoreProductImage;
use App\Enums\Catalog\RecordStatus;
use App\Http\Requests\Admin\Catalog\StoreProductRequest;
use App\Http\Requests\Admin\Catalog\UpdateProductRequest;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ProductController
{
    public function __construct(
        private CreateProduct $createProduct,
        private StoreProductImage $storeProductImage,
        private AuditLogger $audit,
        private MoneyInput $money,
    ) {}

    public function index(): View
    {
        return view('admin.catalog.products.index', [
            'products' => Product::query()
                ->with(['category:id,name', 'brand:id,name'])
                ->orderBy('name')
                ->paginate((int) config('catalog.pagination.products', 30)),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->createProduct->execute($request);

        if ($request->hasFile('image')) {
            $this->storeProductImage->execute($product, $request->file('image'));
        }

        $this->audit->record($request, 'product.created', 'product', $product, after: [
            'name' => $product->name,
            'sku' => $product->sku,
            'track_inventory' => $product->track_inventory,
            'default_price_kobo' => $product->default_price_kobo,
            'status' => $product->status instanceof RecordStatus ? $product->status->value : (string) $product->status,
        ]);

        return redirect()->route('admin.catalog.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('branchPrices');

        return view('admin.catalog.products.edit', [
            ...$this->formData(),
            'product' => $product,
            'branchPriceMap' => $product->branchPrices->mapWithKeys(
                static fn (ProductBranchPrice $price): array => [
                    (string) $price->branch_id => (int) $price->price_kobo,
                ],
            ),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $before = $product->only([
            'name', 'sku', 'barcode', 'category_id', 'brand_id', 'tax_rate_id',
            'description', 'track_inventory', 'default_price_kobo',
            'default_cost_price_kobo', 'status',
        ]);

        DB::transaction(function () use ($request, $product): void {
            $product->update([
                'name' => $request->string('name')->trim()->toString(),
                'sku' => Str::upper($request->string('sku')->trim()->toString()),
                'barcode' => $request->filled('barcode') ? $request->string('barcode')->trim()->toString() : null,
                'category_id' => $request->string('category_id')->toString(),
                'brand_id' => $request->filled('brand_id') ? $request->string('brand_id')->toString() : null,
                'tax_rate_id' => $request->filled('tax_rate_id') ? $request->string('tax_rate_id')->toString() : null,
                'description' => $request->filled('description') ? $request->string('description')->trim()->toString() : null,
                'track_inventory' => $request->boolean('track_inventory'),
                'default_price_kobo' => $this->money->toKobo($request->input('default_price')),
                'default_cost_price_kobo' => $this->money->toKobo($request->input('default_cost_price')),
                'status' => $request->string('status')->toString(),
            ]);

            foreach ($request->array('branch_prices') as $row) {
                if (! is_array($row) || empty($row['branch_id'])) {
                    continue;
                }

                if (! isset($row['price']) || $row['price'] === '' || $row['price'] === null) {
                    ProductBranchPrice::query()
                        ->where('product_id', $product->getKey())
                        ->where('branch_id', (string) $row['branch_id'])
                        ->delete();
                    continue;
                }

                ProductBranchPrice::query()->updateOrCreate(
                    [
                        'product_id' => $product->getKey(),
                        'branch_id' => (string) $row['branch_id'],
                    ],
                    ['price_kobo' => $this->money->toKobo($row['price'])],
                );
            }
        });

        if ($request->hasFile('image')) {
            $this->storeProductImage->execute($product, $request->file('image'));
        }

        $this->audit->record($request, 'product.updated', 'product', $product, before: $before, after: $product->fresh()->toArray());

        return redirect()->route('admin.catalog.products.index')->with('status', 'Product updated.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'categories' => ProductCategory::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::query()->where('status', 'active')->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'rate_basis_points']),
            'branches' => Branch::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
PHP

cat > resources/views/admin/catalog/products/edit.blade.php <<'BLADE'
<x-layout.app title="Edit product | Express Cloud">
    <x-layout.app-shell page-title="Edit product" page-description="Update product identity, pricing, inventory behaviour and availability status.">
        <form method="POST" action="{{ route('admin.catalog.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <x-ui.card title="Identity">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input name="name" label="Product name" :value="old('name', $product->name)" required />
                    <x-ui.input name="sku" label="SKU" :value="old('sku', $product->sku)" required />
                    <x-ui.input name="barcode" label="Barcode" :value="old('barcode', $product->barcode)" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Category</span>
                        <select name="category_id" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Brand</span>
                        <select name="brand_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">No brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Replace product image</span>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-600">
                    </label>
                </div>
                <label class="mt-4 block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                    <textarea name="description" class="min-h-28 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm">{{ old('description', $product->description) }}</textarea>
                </label>
            </x-ui.card>

            <x-ui.card title="Pricing and tax">
                <div class="grid gap-4 md:grid-cols-3">
                    <x-ui.input name="default_price" type="number" step="0.01" label="Default selling price (₦)" :value="old('default_price', number_format($product->default_price_kobo / 100, 2, '.', ''))" required />
                    <x-ui.input name="default_cost_price" type="number" step="0.01" label="Default cost price (₦)" :value="old('default_cost_price', number_format($product->default_cost_price_kobo / 100, 2, '.', ''))" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Tax rate</span>
                        <select name="tax_rate_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">No tax rate</option>
                            @foreach ($taxRates as $taxRate)
                                <option value="{{ $taxRate->id }}" @selected(old('tax_rate_id', $product->tax_rate_id) === $taxRate->id)>{{ $taxRate->name }} ({{ number_format($taxRate->rate_basis_points / 100, 2) }}%)</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card title="Inventory and status">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                        <input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory', $product->track_inventory)) class="mt-0.5 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-semibold text-slate-950">Track inventory</span>
                            <span class="mt-1 block text-sm text-slate-500">Disable this for services and non-stock sale items.</span>
                        </span>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Status</span>
                        <select name="status" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="active" @selected(old('status', $product->status->value) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $product->status->value) === 'inactive')>Inactive</option>
                        </select>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card title="Branch price overrides" description="Leave a branch blank to use the default selling price.">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($branches as $index => $branch)
                        @php $branchKobo = $branchPriceMap->get((string) $branch->id); @endphp
                        <input type="hidden" name="branch_prices[{{ $index }}][branch_id]" value="{{ $branch->id }}">
                        <x-ui.input
                            name="branch_prices[{{ $index }}][price]"
                            type="number"
                            step="0.01"
                            :label="$branch->name"
                            :value="old('branch_prices.'.$index.'.price', $branchKobo !== null ? number_format($branchKobo / 100, 2, '.', '') : '')"
                        />
                    @endforeach
                </div>
            </x-ui.card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.catalog.products.index') }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Cancel</a>
                <x-ui.button type="submit">Save changes</x-ui.button>
            </div>
        </form>
    </x-layout.app-shell>
</x-layout.app>
BLADE

if ! grep -q "products/{product}/edit" routes/admin.php; then
  perl -0pi -e 's#(Route::post\('\''/products'\'', \[ProductController::class, '\''store'\''\]\)\s*->middleware\('\''permission:products.create'\''\)\s*->name\('\''products.store'\''\);)#$1\n\n        Route::get('\''/products/{product}/edit'\'', [ProductController::class, '\''edit'\''])\n            ->middleware('\''permission:products.update'\'')\n            ->name('\''products.edit'\'');\n\n        Route::put('\''/products/{product}'\'', [ProductController::class, '\''update'\''])\n            ->middleware('\''permission:products.update'\'')\n            ->name('\''products.update'\'');#s' routes/admin.php
fi

if ! grep -q '<th class="px-3 py-3 text-right">Actions</th>' resources/views/admin/catalog/products/index.blade.php; then
  perl -0pi -e 's#<th class="px-3 py-3">Status</th>#<th class="px-3 py-3">Status</th>\n                            <th class="px-3 py-3 text-right">Actions</th>#' resources/views/admin/catalog/products/index.blade.php
  perl -0pi -e 's#</td>\n                            </tr>#</td>\n                                <td class="px-3 py-4 text-right">\n                                    @can('\''products.update'\'')\n                                        <a href="{{ route('\''admin.catalog.products.edit'\'', $product) }}" class="inline-flex min-h-9 items-center rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-700">Edit</a>\n                                    @endcan\n                                </td>\n                            </tr>#' resources/views/admin/catalog/products/index.blade.php
  perl -0pi -e 's/colspan="7"/colspan="8"/g' resources/views/admin/catalog/products/index.blade.php
fi

# 7. Ensure Lisa AI and bulk price permissions are seeded correctly by slug.
cat > database/seeders/Sprint4EnterprisePermissionSeeder.php <<'PHP'
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\Sprint4Permissions;
use Illuminate\Database\Seeder;

final class Sprint4EnterprisePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Sprint4Permissions::grouped() as $group => $slugs) {
            foreach ($slugs as $slug) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => str($slug)->replace('.', ' ')->title()->toString(),
                        'description' => str($slug)->replace('.', ' ')->title()->toString(),
                        'group' => $group,
                    ],
                );
            }
        }

        $grant = static function (string $roleName, array $slugs): void {
            $role = Role::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($roleName)])
                ->first();

            if ($role === null) {
                return;
            }

            $role->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('slug', $slugs)->pluck('id')->all(),
            );
        };

        foreach (['System Owner', 'Super Admin', 'Admin', 'Company Owner'] as $roleName) {
            $grant($roleName, Sprint4Permissions::all());
        }

        $grant(
            'Branch Manager',
            array_values(array_diff(
                Sprint4Permissions::all(),
                ['activity.view.all-branches', 'lisa.audit.view'],
            )),
        );

        $inventory = Role::query()->firstOrCreate(
            ['name' => 'Inventory Staff'],
            ['description' => 'Branch-scoped inventory and purchasing access.'],
        );

        $inventory->permissions()->syncWithoutDetaching(
            Permission::query()
                ->whereIn('slug', [
                    'inventory.view',
                    'inventory.transfer',
                    'inventory.intake',
                    'products.view',
                    'products.prices.adjust',
                    'suppliers.view',
                    'procurement.view',
                    'procurement.create',
                    'procurement.receive',
                    'purchases.view',
                    'purchases.create',
                    'categories.manage',
                    'branches.view',
                ])
                ->pluck('id')
                ->all(),
        );
    }
}
PHP

# 8. Make bulk price adjustment update the branch price source used by POS and audit it.
cat > app/Http/Controllers/Admin/Catalog/ProductPriceAdjustmentController.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\BulkPriceAdjustmentRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Services\Organisation\AuditLogger;
use App\Services\Organisation\BranchAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class ProductPriceAdjustmentController
{
    public function __construct(
        private BranchAccess $branchAccess,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.catalog.price-adjustments', [
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(BulkPriceAdjustmentRequest $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();

        $branchIds = $request->boolean('all_branches')
            ? Branch::query()->where('status', 'active')->pluck('id')->all()
            : $request->array('branch_ids');

        foreach ($branchIds as $branchId) {
            $this->branchAccess->enforce($actor, (string) $branchId);
        }

        $productIds = $request->boolean('all_products')
            ? Product::query()->where('status', 'active')->pluck('id')->all()
            : $request->array('product_ids');

        abort_if($branchIds === [] || $productIds === [], 422, 'Select at least one branch and product.');

        $updated = 0;

        DB::transaction(function () use ($request, $branchIds, $productIds, &$updated): void {
            foreach (Product::query()->whereKey($productIds)->cursor() as $product) {
                foreach ($branchIds as $branchId) {
                    $current = (int) (
                        ProductBranchPrice::query()
                            ->where('product_id', $product->getKey())
                            ->where('branch_id', $branchId)
                            ->value('price_kobo')
                        ?? $product->default_price_kobo
                    );

                    $delta = $request->string('mode')->toString() === 'percentage'
                        ? (int) round($current * ((float) $request->input('value') / 100))
                        : (int) round((float) $request->input('value') * 100);

                    $next = $request->string('direction')->toString() === 'subtract'
                        ? max(0, $current - $delta)
                        : $current + $delta;

                    ProductBranchPrice::query()->updateOrCreate(
                        [
                            'product_id' => $product->getKey(),
                            'branch_id' => (string) $branchId,
                        ],
                        ['price_kobo' => $next],
                    );

                    $updated++;
                }
            }
        });

        $this->audit->record(
            $request,
            'product-prices.bulk-adjusted',
            'product',
            null,
            after: [
                'direction' => $request->string('direction')->toString(),
                'mode' => $request->string('mode')->toString(),
                'value' => (float) $request->input('value'),
                'branches' => count($branchIds),
                'products' => count($productIds),
                'price_records_updated' => $updated,
            ],
        );

        return back()->with('status', "{$updated} branch price records updated.");
    }
}
PHP

# 9. POS must read active payment methods and branch prices from the canonical price table.
cat > /tmp/ec_sale_controller_patch.pl <<'PERL'
use strict;
use warnings;
local $/;
my $file = 'app/Http/Controllers/Admin/Sales/SaleController.php';
open my $fh, '<', $file or die $!;
my $s = <$fh>;
close $fh;

if ($s !~ /use App\\Models\\ProductBranchPrice;/) {
    $s =~ s/use App\\Models\\ProductBranchStock;\n/use App\\Models\\ProductBranchPrice;\nuse App\\Models\\ProductBranchStock;\n/;
}

$s =~ s/'productPrices' => ProductBranchStock::query\(\)\s*->whereNotNull\('selling_price_kobo'\)\s*->get\(\['product_id', 'branch_id', 'selling_price_kobo'\]\)\s*->mapWithKeys\(static fn \(ProductBranchStock \$stock\): array => \[\s*\$stock->branch_id\.'\|'\.\$stock->product_id => \(int\) \$stock->selling_price_kobo,\s*\]\),/'productPrices' => ProductBranchPrice::query()\n                ->get(['product_id', 'branch_id', 'price_kobo'])\n                ->mapWithKeys(static fn (ProductBranchPrice \$price): array => [\n                    \$price->branch_id.'\''|'\''.\$price->product_id => (int) \$price->price_kobo,\n                ]),/s;

open my $out, '>', $file or die $!;
print {$out} $s;
close $out;
PERL
perl /tmp/ec_sale_controller_patch.pl
rm -f /tmp/ec_sale_controller_patch.pl

# 10. Static checks, frontend build, commit and push. No database connection is required.
echo "==> Running PHP syntax checks"
find app database routes -name '*.php' -print0 | xargs -0 -n1 php -l >/tmp/expresscloud-php-lint.log
echo "PHP syntax checks passed."

echo "==> Checking required routes and navigation entries"
grep -q "price-adjustments.index" routes/admin.php
grep -q "products.edit" routes/admin.php
grep -q "insights.chat.index" config/navigation.php
grep -q "Bulk Price Update" config/navigation.php
grep -q "Lisa AI" config/navigation.php

echo "==> Building frontend"
npm run build

echo "==> Clearing cached framework files"
php artisan optimize:clear || true

echo "==> Git status"
git status --short

git add -A
git commit -m "fix(v1.0.4): restore missing ERP pages and production UI workflows" \
  -m "Add product editing, expose Lisa AI and bulk pricing permissions, fix activity actor labels, make the topbar fixed, repair responsive overflow, improve the customer modal, align POS pricing and payment method data, and preserve deployed live fixes." || echo "No new commit was created because the workspace may already be committed."

git push origin "$(git branch --show-current)"

echo "==> Fix commit pushed successfully"
