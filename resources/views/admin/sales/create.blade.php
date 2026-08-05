<x-layout.app title="New sale | Express Cloud">
    @php
        $paymentMethods = DB::table('payment_methods')
            ->where('is_active', true)
            ->orderBy('is_default_for_pos', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default_for_pos'])
            ->map(fn ($method) => [
                'id' => (string) $method->id,
                'name' => $method->name,
                'is_default_for_pos' => (bool) $method->is_default_for_pos,
            ])
            ->values()
            ->toArray();

        $customers = DB::table('customers')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn ($customer) => [
                'id' => (string) $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ])
            ->values()
            ->toArray();

        // $products is a paginator, so pull ->items() before mapping.
        // Frontend search/filter/category-pills only cover the current page.
        //
        // FIX: `category` was being assigned as a raw Eloquent object/relation
        // in some setups, which JSON-encodes fine but then renders in the
        // browser as the literal text "[object Object]" wherever it's shown
        // as a string (pill labels, card text). Normalize it to a plain
        // string here so the frontend never has to guess what it received.
        $productCatalog = collect($products->items())
            ->map(function ($product) {
                $rawCategory = $product->category ?? null;
                $category = match (true) {
                    is_object($rawCategory) => data_get($rawCategory, 'name', 'General'),
                    filled($rawCategory) => (string) $rawCategory,
                    default => 'General',
                };

                return [
                    'id' => (string) $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $category,
                    'default_price_kobo' => (int) $product->default_price_kobo,
                    'track_inventory' => (bool) $product->track_inventory,
                ];
            })
            ->values()
            ->toArray();
    @endphp

    <style>
        :root {
            --ec-green: #007a5c;
            --ec-green-dark: #005e46;
            --ec-ink: #1a1a1a;
            --ec-ink-soft: #4b4f54;
            --ec-surface: #ffffff;
            --ec-surface-subdued: #f7f7f8;
            --ec-border: #e3e4e6;
            --ec-border-strong: #c9cbce;
            --ec-critical: #d72c0d;
            --ec-critical-bg: #fdeceb;
            --ec-warning: #a5760a;
            --ec-warning-bg: #fdf3e0;
            --ec-success: #0a6640;
            --ec-success-bg: #e3f5ec;
            --ec-radius: 10px;
            --ec-radius-sm: 6px;
            --ec-shadow: 0 1px 0 rgba(0,0,0,.03), 0 1px 3px rgba(0,0,0,.06);
            --ec-shadow-hover: 0 4px 10px rgba(0,0,0,.08);
        }

        [x-cloak] { display: none !important; }

        .ec-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 16px;
            align-items: start;
        }
        @media (max-width: 1180px) {
            .ec-shell { grid-template-columns: 1fr; }
        }

        .ec-panel {
            background: var(--ec-surface);
            border: 1px solid var(--ec-border);
            border-radius: var(--ec-radius);
            box-shadow: var(--ec-shadow);
            overflow: hidden;
        }

        /* toolbar */
        .ec-toolbar {
            display: grid;
            grid-template-columns: minmax(160px, 220px) minmax(0, 1fr);
            gap: 10px;
            padding: 14px;
            border-bottom: 1px solid var(--ec-border);
            background: var(--ec-surface-subdued);
        }
        @media (max-width: 640px) {
            .ec-toolbar { grid-template-columns: 1fr; }
        }
        .ec-select, .ec-input {
            min-height: 40px;
            border-radius: var(--ec-radius-sm);
            border: 1px solid var(--ec-border-strong);
            background: var(--ec-surface);
            padding: 0 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ec-ink);
            outline: none;
            transition: border-color .12s, box-shadow .12s;
        }
        .ec-select:focus, .ec-input:focus {
            border-color: var(--ec-ink);
            box-shadow: 0 0 0 3px rgba(26,26,26,.08);
        }
        .ec-search-wrap { position: relative; }
        .ec-search-wrap .ec-input { width: 100%; padding-left: 38px; font-weight: 500; }
        .ec-search-icon {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--ec-ink-soft); pointer-events: none;
        }

        /* category pills */
        .ec-tabs {
            display: flex; align-items: center; gap: 6px; padding: 10px 14px; overflow-x: auto;
            border-bottom: 1px solid var(--ec-border);
        }
        .ec-tabs::-webkit-scrollbar { height: 4px; }
        .ec-pill {
            flex: none;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid var(--ec-border-strong);
            background: var(--ec-surface);
            color: var(--ec-ink-soft);
            white-space: nowrap;
            cursor: pointer;
            transition: all .12s;
        }
        .ec-pill:hover { border-color: var(--ec-ink); color: var(--ec-ink); }
        .ec-pill.is-active { background: var(--ec-ink); border-color: var(--ec-ink); color: #fff; }

        .ec-stock-toggle {
            display: flex; align-items: center; gap: 6px;
            margin-left: auto; padding-left: 10px; flex: none;
            font-size: 12px; font-weight: 600; color: var(--ec-ink-soft);
            white-space: nowrap;
        }

        /* product grid */
        .ec-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 10px;
            padding: 14px;
            max-height: 44vh;
            overflow-y: auto;
        }
        .ec-card {
            text-align: left;
            border: 1px solid var(--ec-border);
            border-radius: var(--ec-radius-sm);
            background: var(--ec-surface);
            padding: 12px;
            cursor: pointer;
            transition: box-shadow .12s, border-color .12s, transform .12s;
        }
        .ec-card:hover:not(:disabled) {
            border-color: var(--ec-ink);
            box-shadow: var(--ec-shadow-hover);
            transform: translateY(-1px);
        }
        .ec-card:disabled { opacity: .5; cursor: not-allowed; background: var(--ec-surface-subdued); }
        .ec-card-sku { font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--ec-ink-soft); }
        .ec-card-name { margin-top: 6px; font-size: 13px; font-weight: 700; color: var(--ec-ink); line-height: 1.3; min-height: 34px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .ec-card-price { margin-top: 10px; font-size: 14px; font-weight: 800; color: var(--ec-ink); }
        .ec-badge { display: inline-flex; align-items: center; gap: 4px; margin-top: 8px; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 800; }
        .ec-badge-success { background: var(--ec-success-bg); color: var(--ec-success); }
        .ec-badge-warning { background: var(--ec-warning-bg); color: var(--ec-warning); }
        .ec-badge-critical { background: var(--ec-critical-bg); color: var(--ec-critical); }
        .ec-badge-neutral { background: var(--ec-surface-subdued); color: var(--ec-ink-soft); }

        /* selected products strip — its own row + nested scrollbar, never clipped */
        .ec-selected {
            border-top: 1px solid var(--ec-border);
            background: var(--ec-surface-subdued);
        }
        .ec-selected-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px;
        }
        .ec-selected-head h3 { font-size: 13px; font-weight: 800; color: var(--ec-ink); margin: 0; }
        .ec-selected-count { font-size: 11.5px; font-weight: 700; color: var(--ec-ink-soft); }
        .ec-selected-list {
            max-height: 260px;
            overflow-y: auto;
            padding: 0 14px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .ec-selected-empty {
            padding: 20px 14px;
            text-align: center;
            font-size: 12.5px;
            color: var(--ec-ink-soft);
        }

        .ec-line { border: 1px solid var(--ec-border); border-radius: var(--ec-radius-sm); padding: 10px; background: var(--ec-surface); }
        .ec-line-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .ec-line-name { font-size: 13px; font-weight: 700; color: var(--ec-ink); }
        .ec-line-price { font-size: 11.5px; color: var(--ec-ink-soft); margin-top: 2px; }
        .ec-remove-btn { color: var(--ec-ink-soft); background: none; border: none; cursor: pointer; padding: 2px; border-radius: 4px; flex: none; }
        .ec-remove-btn:hover { color: var(--ec-critical); background: var(--ec-critical-bg); }

        .ec-line-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 8px; }

        .ec-qty { display: flex; align-items: center; border: 1px solid var(--ec-border-strong); border-radius: var(--ec-radius-sm); overflow: hidden; flex: none; }
        .ec-qty button { width: 26px; height: 26px; background: var(--ec-surface-subdued); border: none; font-weight: 800; cursor: pointer; color: var(--ec-ink); }
        .ec-qty input { width: 40px; height: 26px; border: none; text-align: center; font-size: 12px; font-weight: 700; }

        .ec-disc-row { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 140px; }
        .ec-disc-toggle { display: flex; border: 1px solid var(--ec-border-strong); border-radius: var(--ec-radius-sm); overflow: hidden; flex: none; }
        .ec-disc-toggle button { width: 26px; height: 28px; font-size: 11px; font-weight: 800; background: var(--ec-surface); border: none; cursor: pointer; color: var(--ec-ink-soft); }
        .ec-disc-toggle button.is-active { background: var(--ec-ink); color: #fff; }
        .ec-disc-input { flex: 1; min-width: 0; min-height: 28px; border-radius: var(--ec-radius-sm); border: 1px solid var(--ec-border-strong); padding: 0 8px; font-size: 12px; }
        .ec-line-total { margin-top: 8px; text-align: right; font-size: 13px; font-weight: 800; color: var(--ec-ink); }

        /* cart / summary sidebar — short by design, no forced max-height */
        .ec-cart { position: sticky; top: 12px; display: flex; flex-direction: column; }
        .ec-cart-head { display: flex; align-items: center; justify-content: space-between; padding: 14px; border-bottom: 1px solid var(--ec-border); }
        .ec-type-tabs { display: flex; border: 1px solid var(--ec-border-strong); border-radius: var(--ec-radius-sm); overflow: hidden; }
        .ec-type-tabs button { padding: 7px 12px; font-size: 11.5px; font-weight: 800; background: var(--ec-surface); color: var(--ec-ink-soft); border: none; cursor: pointer; }
        .ec-type-tabs button.is-active { background: var(--ec-ink); color: #fff; }

        .ec-order-discount { border: 1px dashed var(--ec-border-strong); border-radius: var(--ec-radius-sm); padding: 10px; margin: 12px; background: var(--ec-surface-subdued); }
        .ec-order-discount-head { display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: var(--ec-ink-soft); margin-bottom: 8px; }

        .ec-payments { padding: 0 12px 12px; }
        .ec-payments-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .ec-add-link { font-size: 11.5px; font-weight: 800; color: var(--ec-green); background: none; border: none; cursor: pointer; }
        .ec-payment-list { max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 2px; }

        .ec-summary { border-top: 1px solid var(--ec-border); background: var(--ec-surface-subdued); padding: 14px; }
        .ec-summary-row { display: flex; justify-content: space-between; font-size: 13px; color: var(--ec-ink-soft); padding: 3px 0; }
        .ec-summary-row.total { font-size: 17px; font-weight: 900; color: var(--ec-ink); border-top: 1px solid var(--ec-border); margin-top: 6px; padding-top: 8px; }
        .ec-summary-row.balance { font-weight: 800; }
        .ec-summary-row.balance.is-due { color: var(--ec-critical); }
        .ec-summary-row.balance.is-paid { color: var(--ec-success); }

        .ec-btn { min-height: 42px; border-radius: var(--ec-radius-sm); font-size: 13px; font-weight: 800; cursor: pointer; border: 1px solid transparent; padding: 0 16px; }
        .ec-btn-primary { background: var(--ec-ink); color: #fff; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .ec-btn-primary:hover:not(:disabled) { background: var(--ec-green-dark); }
        .ec-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
        .ec-btn-secondary { background: var(--ec-surface); border-color: var(--ec-border-strong); color: var(--ec-ink); }
        .ec-btn-secondary:hover { background: var(--ec-surface-subdued); }
    </style>

    <x-layout.app-shell
        page-title="POS checkout"
        page-description="Barcode-first checkout, branch stock visibility, split payments, invoices and quotes."
    >
        {{-- ACTIONS BAR — dead branch dropdown removed, customer search kept --}}
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative" x-data="{
                    customerSearch: '',
                    customers: @js($customers),
                    filteredCustomers() {
                        if (!this.customerSearch.trim()) return this.customers;
                        const term = this.customerSearch.toLowerCase().trim();
                        return this.customers.filter(c =>
                            c.name.toLowerCase().includes(term) ||
                            (c.phone && c.phone.includes(term))
                        );
                    },
                    selectCustomer(customer) {
                        this.customerSearch = customer.name;
                        this.$refs.customerResults.classList.add('hidden');
                        this.$dispatch('customer-selected', { id: customer.id });
                    },
                    clearCustomer() {
                        this.customerSearch = '';
                        this.$refs.customerResults.classList.add('hidden');
                        this.$dispatch('customer-selected', { id: '' });
                    }
                }">
                    <input
                        type="text"
                        x-model="customerSearch"
                        placeholder="Search customer by name or phone..."
                        class="ec-input min-w-[240px]"
                        style="width: 100%;"
                        x-on:focus="$refs.customerResults.classList.remove('hidden')"
                        x-on:click="$refs.customerResults.classList.remove('hidden')"
                        x-on:keydown.escape="$refs.customerResults.classList.add('hidden')"
                        x-on:keydown.enter.prevent="
                            if (filteredCustomers().length === 1) {
                                selectCustomer(filteredCustomers()[0]);
                            }
                        "
                    >
                    <button
                        type="button"
                        x-show="customerSearch !== '' && !filteredCustomers().find(c => c.name === customerSearch)"
                        x-on:click="clearCustomer()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">close</span>
                    </button>
                    <div
                        x-ref="customerResults"
                        class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                        x-show="customerSearch.length > 0 && filteredCustomers().length > 0"
                        x-transition
                        x-cloak
                    >
                        <template x-for="customer in filteredCustomers()" :key="customer.id">
                            <button
                                type="button"
                                x-on:click="selectCustomer(customer)"
                                class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-slate-50 border-b border-slate-100 last:border-b-0"
                            >
                                <span x-text="customer.name"></span>
                                <span x-show="customer.phone" class="text-xs text-slate-400" x-text="customer.phone"></span>
                            </button>
                        </template>
                    </div>
                    <div
                        class="absolute left-0 right-0 z-50 mt-1 rounded-xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-500 shadow-lg"
                        x-show="customerSearch.length > 0 && filteredCustomers().length === 0"
                        x-transition
                        x-cloak
                    >
                        No customers found.
                        <button
                            type="button"
                            x-on:click="$dispatch('open-customer-modal')"
                            class="font-semibold text-blue-700 hover:underline"
                        >
                            Create new customer
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click="$dispatch('open-customer-modal')"
                    class="ec-btn ec-btn-secondary inline-flex items-center gap-1.5"
                >
                    <x-ui.icon name="plus" :size="16" />
                    New customer
                </button>
            </div>
        </x-slot:actions>

        {{-- MAIN COMPONENT --}}
        <div
            x-data="posSaleNew(@js($productCatalog), @js($productStocks), @js($productPrices), @js($paymentMethods), @js(old('branch_id', $branches->first()?->id ?? '')))"
            x-on:customer-selected.window="customerId = $event.detail.id"
            class="min-h-[calc(100vh-9rem)]"
        >
            <form id="pos-form" method="POST" action="{{ route('admin.sales.store') }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="sale_type" x-bind:value="saleType">
                <input type="hidden" name="branch_id" x-bind:value="branchId">
                <input type="hidden" name="customer_id" x-bind:value="customerId">
                <input type="hidden" name="notes" x-bind:value="notes">
                <input type="hidden" name="order_discount_type" x-bind:value="orderDiscount.type">
                <input type="hidden" name="order_discount_value" x-bind:value="orderDiscount.value">
                <input type="hidden" name="order_discount_amount" x-bind:value="(orderDiscountAmountKobo() / 100).toFixed(2)">
                @include('admin.sales.partials.quick-customer-modal')

                <div class="ec-shell">
                    {{-- PRODUCT PANEL --}}
                    <section class="ec-panel">
                        <div class="ec-toolbar">
                            <select x-model="branchId" required class="ec-select">
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->code }} · {{ $branch->name }}</option>
                                @endforeach
                            </select>

                            <div class="ec-search-wrap">
                                <span class="ec-search-icon">
                                    <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">barcode_scanner</span>
                                </span>
                                <input
                                    type="search"
                                    x-model="searchTerm"
                                    placeholder="Scan barcode or search product name / SKU"
                                    class="ec-input"
                                >
                            </div>
                        </div>

                        <div class="ec-tabs">
                            <template x-for="cat in categories" :key="cat">
                                <button
                                    type="button"
                                    x-on:click="activeCategory = cat"
                                    x-bind:class="activeCategory === cat ? 'ec-pill is-active' : 'ec-pill'"
                                    x-text="cat"
                                ></button>
                            </template>
                            <label class="ec-stock-toggle">
                                <input type="checkbox" x-model="inStockOnly">
                                In stock only
                            </label>
                        </div>

                        <div x-show="!branchId" class="m-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Select a branch to load branch-specific availability.
                        </div>

                        <div x-show="branchId" class="ec-product-grid">
                            <template x-for="product in filteredProducts" :key="product.id">
                                <button
                                    type="button"
                                    x-on:click="addProduct(product)"
                                    x-bind:disabled="!canAdd(product)"
                                    class="ec-card"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="ec-card-sku" x-text="product.sku"></span>
                                    </div>
                                    <h3 class="ec-card-name" x-text="product.name"></h3>
                                    <div class="ec-card-price" x-text="money(branchPrice(product))"></div>
                                    <span
                                        class="ec-badge"
                                        x-bind:class="'ec-badge-' + stockBadge(product).tone"
                                        x-text="stockBadge(product).label"
                                    ></span>
                                </button>
                            </template>
                            <div x-show="filteredProducts.length === 0" class="col-span-full py-10 text-center text-sm text-slate-500">
                                No products match your search.
                            </div>
                        </div>

                        {{-- SELECTED PRODUCTS — its own row under the grid, own nested scrollbar --}}
                        <div class="ec-selected" x-show="branchId">
                            <div class="ec-selected-head">
                                <h3>Selected products</h3>
                                <span class="ec-selected-count"><span x-text="cart.length"></span> line<span x-show="cart.length !== 1">s</span></span>
                            </div>

                            <div x-show="cart.length === 0" class="ec-selected-empty">
                                Nothing selected yet — tap a product above to add it here.
                            </div>

                            <div class="ec-selected-list" x-show="cart.length > 0">
                                <template x-for="line in cart" :key="line.id">
                                    <article class="ec-line">
                                        <div class="ec-line-top">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="ec-line-name truncate" x-text="line.name"></h3>
                                                <p class="ec-line-price" x-text="money(line.unit_price_kobo) + ' each'"></p>
                                            </div>
                                            <button type="button" x-on:click="removeLine(line.id)" class="ec-remove-btn">
                                                <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">close</span>
                                            </button>
                                        </div>

                                        <div class="ec-line-controls">
                                            <div class="ec-qty">
                                                <button type="button" x-on:click="changeQuantity(line, -1)">−</button>
                                                <input
                                                    type="number" min="1" step="1"
                                                    x-model.number="line.quantity"
                                                    :max="line.track_inventory ? stockFor(line) : null"
                                                    @input="setQuantity(line, $event.target.value)"
                                                    @change="setQuantity(line, $event.target.value)"
                                                    aria-label="Item quantity"
                                                >
                                                <button type="button" x-on:click="changeQuantity(line, 1)">+</button>
                                            </div>

                                            <div class="ec-disc-row">
                                                <div class="ec-disc-toggle">
                                                    <button type="button" x-on:click="line.discount_type = 'percent'" x-bind:class="line.discount_type === 'percent' ? 'is-active' : ''">%</button>
                                                    <button type="button" x-on:click="line.discount_type = 'fixed'" x-bind:class="line.discount_type === 'fixed' ? 'is-active' : ''">₦</button>
                                                </div>
                                                <input
                                                    type="number" min="0" step="0.01"
                                                    x-model.number="line.discount_value"
                                                    :max="line.discount_type === 'percent' ? 100 : null"
                                                    placeholder="Discount"
                                                    class="ec-disc-input"
                                                >
                                            </div>
                                        </div>

                                        <div class="ec-line-total" x-text="money((line.unit_price_kobo * line.quantity) - lineDiscountAmountKobo(line))"></div>
                                    </article>
                                </template>
                            </div>
                        </div>
                    </section>

                    {{-- CART / SUMMARY PANEL — kept short so nothing gets clipped --}}
                    <aside class="ec-panel ec-cart">
                        <div class="ec-cart-head">
                            <h2 class="text-base font-bold text-slate-950">Sale type</h2>
                            <div class="ec-type-tabs">
                                <button type="button" x-on:click="switchType('pos')" x-bind:class="saleType === 'pos' ? 'is-active' : ''">POS</button>
                                <button type="button" x-on:click="switchType('invoice')" x-bind:class="saleType === 'invoice' ? 'is-active' : ''">Invoice</button>
                                <button type="button" x-on:click="switchType('quote')" x-bind:class="saleType === 'quote' ? 'is-active' : ''">Quote</button>
                            </div>
                        </div>

                        <div class="ec-order-discount" x-show="cart.length > 0">
                            <div class="ec-order-discount-head">
                                <span>Order-level discount</span>
                            </div>
                            <div class="ec-disc-row" style="margin-top: 0;">
                                <div class="ec-disc-toggle">
                                    <button type="button" x-on:click="orderDiscount.type = 'percent'" x-bind:class="orderDiscount.type === 'percent' ? 'is-active' : ''">%</button>
                                    <button type="button" x-on:click="orderDiscount.type = 'fixed'" x-bind:class="orderDiscount.type === 'fixed' ? 'is-active' : ''">₦</button>
                                </div>
                                <input
                                    type="number" min="0" step="0.01"
                                    x-model.number="orderDiscount.value"
                                    :max="orderDiscount.type === 'percent' ? 100 : null"
                                    placeholder="Applied to whole order"
                                    class="ec-disc-input"
                                >
                            </div>
                        </div>

                        <div x-show="saleType !== 'quote'" class="ec-payments">
                            <div class="ec-payments-head">
                                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Split payments</h3>
                                <button type="button" x-on:click="addPayment" class="ec-add-link">Add payment</button>
                            </div>
                            <div class="ec-payment-list">
                                <template x-for="(payment, index) in payments" :key="index">
                                    <div class="grid grid-cols-[minmax(0,1fr)_100px_30px] gap-2">
                                        <select x-model="payment.payment_method_id" class="ec-select" style="min-height: 34px; font-size: 12px;">
                                            <option value="">Select method</option>
                                            @foreach ($paymentMethods as $method)
                                                <option value="{{ $method['id'] }}">{{ $method['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <input x-model="payment.amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="ec-input" style="min-height: 34px; font-size: 12px;">
                                        <button type="button" x-on:click="removePayment(index)" class="rounded-lg border border-slate-200 text-red-600">×</button>
                                        <input x-model="payment.reference" class="ec-input col-span-3" style="min-height: 32px; font-size: 12px;" placeholder="Reference (optional)">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="ec-summary">
                            @if ($errors->any())
                                <div class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                                    <ul class="list-disc pl-4 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="ec-summary-row"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
                            <div class="ec-summary-row"><span>Line discounts</span><span x-text="'− ' + money(lineDiscountTotal)"></span></div>
                            <div class="ec-summary-row" x-show="orderDiscountAmountKobo() > 0">
                                <span>Order discount</span><span x-text="'− ' + money(orderDiscountAmountKobo())"></span>
                            </div>
                            <div class="ec-summary-row total"><span>Total</span><span x-text="money(total)"></span></div>
                            <div x-show="saleType !== 'quote'" class="ec-summary-row balance" x-bind:class="balance > 0 ? 'is-due' : 'is-paid'">
                                <span>Balance</span><span x-text="money(balance)"></span>
                            </div>

                            <div id="customer-error" class="mt-2 hidden rounded-lg bg-red-50 p-3 text-sm text-red-700">
                                ️ Please select a customer before submitting.
                            </div>

                            <textarea x-model="notes" rows="2" placeholder="Sale notes" class="ec-input mt-3 w-full" style="min-height: 60px; padding: 8px 12px;"></textarea>

                            <div class="mt-3 grid grid-cols-[auto_1fr] gap-2">
                                <button type="button" x-on:click="clearCart" class="ec-btn ec-btn-secondary">Clear</button>
                                <button
                                    type="button"
                                    x-on:click="submitForm"
                                    x-bind:disabled="submitting"
                                    class="ec-btn ec-btn-primary"
                                >
                                    <svg x-show="submitting" class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="submitting ? 'Working…' : (saleType === 'quote' ? 'Save quote' : saleType === 'invoice' ? 'Create invoice' : 'Complete sale')"></span>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </x-layout.app-shell>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posSaleNew', (catalog, stockMap, priceMap, paymentMethods, initialBranch = '') => ({
                catalog,
                stockMap,
                priceMap,
                paymentMethods,
                branchId: initialBranch,
                saleType: 'pos',
                cart: [],
                payments: [],
                customerId: '',
                notes: '',
                submitting: false,

                searchTerm: '',
                activeCategory: 'All',
                inStockOnly: false,

                orderDiscount: { type: 'percent', value: 0 },

                init() {
                    this.resetPayments();
                    this.$watch('branchId', () => this.repriceCart());
                },

                // FIX: defensively coerce category to a plain string no matter
                // what shape it arrives in (string, null, or an accidental object)
                // so the UI never prints "[object Object]".
                categoryOf(product) {
                    const c = product?.category;
                    if (c === null || c === undefined || c === '') return 'General';
                    if (typeof c === 'object') return c.name || 'General';
                    return String(c);
                },

                get categories() {
                    const set = new Set(this.catalog.map((p) => this.categoryOf(p)));
                    return ['All', ...Array.from(set).sort()];
                },

                get filteredProducts() {
                    const term = this.searchTerm.trim().toLowerCase();
                    return this.catalog.filter((p) => {
                        const matchesTerm = !term
                            || (p.name || '').toLowerCase().includes(term)
                            || (p.sku || '').toLowerCase().includes(term);
                        const matchesCategory = this.activeCategory === 'All'
                            || this.categoryOf(p) === this.activeCategory;
                        const matchesStock = !this.inStockOnly
                            || !p.track_inventory
                            || this.stockFor(p) > 0;
                        return matchesTerm && matchesCategory && matchesStock;
                    });
                },

                // FIX: guard against a missing/non-numeric stock entry instead
                // of letting NaN or a stray value leak into the label.
                stockFor(product) {
                    if (!product.track_inventory) return null;
                    const raw = Number(this.stockMap[`${this.branchId}|${product.id}`] || 0);
                    return Number.isFinite(raw) ? raw / 1000 : 0;
                },

                formatStock(n) {
                    return Number.isInteger(n) ? n : n.toFixed(2);
                },

                stockBadge(product) {
                    if (!product.track_inventory) return { label: 'Not tracked', tone: 'neutral' };
                    if (!this.branchId) return { label: '—', tone: 'neutral' };
                    const stock = this.stockFor(product);
                    if (stock <= 0) return { label: 'Out of stock', tone: 'critical' };
                    if (stock <= 5) return { label: `${this.formatStock(stock)} left`, tone: 'warning' };
                    return { label: `${this.formatStock(stock)} left`, tone: 'success' };
                },

                // FIX: fall back to the product's default price (and guarantee
                // a finite number) if the branch price map has a bad/missing entry.
                branchPrice(product) {
                    const raw = this.priceMap?.[`${this.branchId}|${product.id}`];
                    const fallback = Number(product.default_price_kobo || 0);
                    if (raw === undefined || raw === null || raw === '') return fallback;
                    const value = Number(raw);
                    return Number.isFinite(value) ? value : fallback;
                },

                repriceCart() {
                    this.cart = this.cart
                        .filter((line) => !line.track_inventory || this.stockFor(line) > 0)
                        .map((line) => ({
                            ...line,
                            quantity: line.track_inventory
                                ? Math.min(Math.max(1, Number(line.quantity) || 1), this.stockFor(line))
                                : Math.max(1, Number(line.quantity) || 1),
                            unit_price_kobo: this.branchPrice(line),
                        }));
                    this.syncDefaultPayment();
                },

                canAdd(product) {
                    return this.branchId && (!product.track_inventory || this.stockFor(product) > 0);
                },

                addProduct(product) {
                    if (!this.canAdd(product)) return;
                    const existing = this.cart.find((line) => line.id === product.id);
                    const stock = this.stockFor(product);
                    if (existing) {
                        if (product.track_inventory && existing.quantity >= stock) return;
                        existing.quantity = product.track_inventory
                            ? Math.min(stock, existing.quantity + 1)
                            : existing.quantity + 1;
                        existing.unit_price_kobo = this.branchPrice(product);
                    } else {
                        this.cart.push({
                            ...product,
                            quantity: 1,
                            discount_type: 'percent',
                            discount_value: 0,
                            unit_price_kobo: this.branchPrice(product),
                        });
                    }
                    this.syncDefaultPayment();
                },

                changeQuantity(line, delta) {
                    this.setQuantity(line, Number(line.quantity) + delta);
                },

                setQuantity(line, value) {
                    const requested = Math.max(1, Math.floor(Number(value) || 1));
                    const stock = this.stockFor(line);
                    line.quantity = line.track_inventory
                        ? Math.min(requested, Math.max(0, stock))
                        : requested;
                    if (line.track_inventory && line.quantity < 1) {
                        this.removeLine(line.id);
                        return;
                    }
                    this.syncDefaultPayment();
                },

                removeLine(id) {
                    this.cart = this.cart.filter((line) => line.id !== id);
                    this.syncDefaultPayment();
                },

                clearCart() {
                    this.cart = [];
                    this.orderDiscount = { type: 'percent', value: 0 };
                    this.resetPayments();
                },

                get subtotal() {
                    return this.cart.reduce((sum, line) => sum + (line.unit_price_kobo * line.quantity), 0);
                },

                lineDiscountAmountKobo(line) {
                    const gross = line.unit_price_kobo * line.quantity;
                    const raw = line.discount_type === 'percent'
                        ? gross * (Number(line.discount_value || 0) / 100)
                        : Number(line.discount_value || 0) * 100;
                    return Math.min(Math.max(raw, 0), gross);
                },

                get lineDiscountTotal() {
                    return this.cart.reduce((sum, line) => sum + this.lineDiscountAmountKobo(line), 0);
                },

                get afterLineDiscount() {
                    return Math.max(0, this.subtotal - this.lineDiscountTotal);
                },

                orderDiscountAmountKobo() {
                    const base = this.afterLineDiscount;
                    const raw = this.orderDiscount.type === 'percent'
                        ? base * (Number(this.orderDiscount.value || 0) / 100)
                        : Number(this.orderDiscount.value || 0) * 100;
                    return Math.min(Math.max(raw, 0), base);
                },

                get total() {
                    return Math.max(0, this.afterLineDiscount - this.orderDiscountAmountKobo());
                },

                get paidTotal() {
                    return this.payments.reduce((sum, payment) => sum + Math.max(0, Number(payment.amount || 0) * 100), 0);
                },

                get balance() {
                    return Math.max(0, this.total - this.paidTotal);
                },

                // FIX: coerce to a finite number before formatting, so a bad
                // value renders as ₦0.00 instead of "[object Object]" or "NaN".
                money(kobo) {
                    const value = Number(kobo);
                    const safe = Number.isFinite(value) ? value : 0;
                    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 2 }).format(safe / 100);
                },

                addPayment() {
                    this.payments.push({ payment_method_id: '', amount: '', reference: '' });
                },

                removePayment(index) {
                    this.payments.splice(index, 1);
                    if (!this.payments.length) this.addPayment();
                },

                resetPayments() {
                    const preferred = this.paymentMethods.find((method) => method.is_default_for_pos) || this.paymentMethods[0];
                    this.payments = [{ payment_method_id: preferred?.id || '', amount: '', reference: '' }];
                },

                syncDefaultPayment() {
                    if (this.saleType === 'quote' || this.payments.length !== 1) return;
                    this.payments[0].amount = (this.total / 100).toFixed(2);
                },

                switchType(type) {
                    this.saleType = type;
                    if (type === 'quote') {
                        this.payments = [];
                    } else if (!this.payments.length) {
                        this.resetPayments();
                        this.syncDefaultPayment();
                    }
                },

                submitForm() {
                    this.submitting = true;

                    if (!this.customerId) {
                        const errorEl = document.getElementById('customer-error');
                        if (errorEl) {
                            errorEl.classList.remove('hidden');
                            errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            alert('Please select a customer.');
                        }
                        this.submitting = false;
                        return;
                    }

                    if (!this.branchId) {
                        alert('Please select a branch.');
                        this.submitting = false;
                        return;
                    }

                    if (this.cart.length === 0) {
                        alert('Please add at least one product to the cart.');
                        this.submitting = false;
                        return;
                    }

                    const errorEl = document.getElementById('customer-error');
                    if (errorEl) errorEl.classList.add('hidden');

                    const form = document.getElementById('pos-form');
                    if (!form) {
                        this.submitting = false;
                        alert('Form not found. Please refresh and try again.');
                        return;
                    }

                    document.querySelectorAll('.dynamic-cart-input, .dynamic-payment-input').forEach((el) => el.remove());

                    const afterLineDiscount = this.afterLineDiscount;
                    const orderDiscountKobo = this.orderDiscountAmountKobo();

                    this.cart.forEach((line, index) => {
                        const gross = line.unit_price_kobo * line.quantity;
                        const lineDiscount = this.lineDiscountAmountKobo(line);
                        const afterLine = gross - lineDiscount;
                        const orderShare = afterLineDiscount > 0
                            ? (afterLine / afterLineDiscount) * orderDiscountKobo
                            : 0;
                        const combinedDiscount = ((lineDiscount + orderShare) / 100).toFixed(2);

                        const fields = {
                            'product_id': line.id,
                            'quantity': line.quantity,
                            'unit_price': (line.unit_price_kobo / 100).toFixed(2),
                            'discount': combinedDiscount,
                            'discount_type': line.discount_type,
                            'discount_value': line.discount_value,
                        };
                        for (const [key, value] of Object.entries(fields)) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `items[${index}][${key}]`;
                            input.value = value;
                            input.className = 'dynamic-cart-input';
                            form.appendChild(input);
                        }
                    });

                    if (this.saleType !== 'quote') {
                        this.payments.forEach((payment, index) => {
                            const amount = parseFloat(payment.amount);
                            if (!payment.payment_method_id || isNaN(amount) || amount <= 0) return;

                            const fields = {
                                'payment_method_id': payment.payment_method_id,
                                'amount': amount.toFixed(2),
                                'reference': payment.reference || '',
                            };
                            for (const [key, value] of Object.entries(fields)) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = `payments[${index}][${key}]`;
                                input.value = value;
                                input.className = 'dynamic-payment-input';
                                form.appendChild(input);
                            }
                        });
                    }

                    form.submit();
                },
            }));
        });
    </script>
</x-layout.app>