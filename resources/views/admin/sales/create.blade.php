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
    @endphp

    <x-layout.app-shell
        page-title="POS checkout"
        page-description="Barcode-first checkout, branch stock visibility, split payments, invoices and quotes."
    >
        {{-- ACTIONS BAR --}}
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <select x-model="branchId" required class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold focus:border-slate-900 focus:ring-slate-900">
                    <option value="">Select branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->code }} · {{ $branch->name }}</option>
                    @endforeach
                </select>

                {{-- Searchable customer --}}
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
                        class="min-h-10 min-w-[220px] rounded-xl border border-slate-300 bg-white px-3 pr-8 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
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
                        <i data-lucide="x" class="h-4 w-4"></i>
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
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    <x-ui.icon name="plus" :size="16" />
                    New customer
                </button>
            </div>
        </x-slot:actions>

        {{-- MAIN COMPONENT --}}
        <div
            x-data="posSaleNew(@js($productStocks), @js($productPrices), @js($paymentMethods), @js(old('branch_id', $branches->first()?->id ?? '')))"
            x-on:customer-selected.window="customerId = $event.detail.id"
            class="min-h-[calc(100vh-9rem)]"
        >
            <form id="pos-form" method="POST" action="{{ route('admin.sales.store') }}" class="grid gap-[5px] xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,.65fr)]">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="sale_type" x-bind:value="saleType">
                <input type="hidden" name="branch_id" x-bind:value="branchId">
                <input type="hidden" name="customer_id" x-bind:value="customerId">
                @include('admin.sales.partials.quick-customer-modal')
                <input type="hidden" name="notes" x-bind:value="notes">

                {{-- CART & PAYMENT hidden inputs are now generated dynamically in submitForm() --}}

                {{-- PRODUCT SECTION --}}
                <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 p-4">
                        <div class="grid gap-3 lg:grid-cols-[minmax(180px,240px)_minmax(0,1fr)]">
                            <select x-model="branchId" required class="min-h-12 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold focus:border-slate-900 focus:ring-slate-900">
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->code }} · {{ $branch->name }}</option>
                                @endforeach
                            </select>

                            <form method="GET" action="{{ route('admin.sales.create') }}" class="relative" x-data="{}" x-on:submit.prevent>
                                <input
                                    type="search"
                                    name="q"
                                    value="{{ request('q') }}"
                                    placeholder="Scan barcode or search product name / SKU"
                                    class="min-h-12 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-4 text-sm outline-none focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                    x-on:input.debounce.300ms="$el.form.submit()"
                                >
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <i data-lucide="scan-barcode" class="h-5 w-5"></i>
                                </span>
                            </form>
                        </div>
                    </div>

                    <div class="max-h-[calc(100vh-15rem)] overflow-y-auto p-4">
                        <div x-show="!branchId" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Select a branch to load branch-specific availability.</div>

                        <div x-show="branchId">
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-4">
                                @forelse ($products as $product)
                                    <button
                                        type="button"
                                        x-on:click="addProduct(@js($product))"
                                        x-bind:disabled="!canAdd(@js($product))"
                                        class="min-w-0 rounded-xl border border-slate-200 bg-white p-3 text-left transition hover:-translate-y-0.5 hover:border-slate-900 hover:shadow-md disabled:cursor-not-allowed disabled:bg-slate-100 disabled:opacity-55"
                                    >
                                        <div class="mb-3 flex items-start justify-between gap-2">
                                            <span class="truncate text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $product->sku }}</span>
                                            @unless ($product->track_inventory)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Service</span>
                                            @endunless
                                        </div>
                                        <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-slate-900">{{ $product->name }}</h3>
                                        <div class="mt-3 flex items-end justify-between gap-2">
                                            <strong class="text-sm text-slate-950">
                                                ₦{{ number_format($product->default_price_kobo / 100, 2) }}
                                            </strong>
                                            <span class="text-right text-[11px] font-medium text-slate-500">
                                                @if ($product->track_inventory)
                                                    In stock
                                                @else
                                                    Not tracked
                                                @endif
                                            </span>
                                        </div>
                                    </button>
                                @empty
                                    <div class="col-span-full py-10 text-center text-sm text-slate-500">No products found.</div>
                                @endforelse
                            </div>

                            <div class="mt-4 border-t border-slate-200 pt-4">
                                {{ $products->links() }}
                            </div>
                        </div>
                    </div>
                </section>

                {{-- CART SIDEBAR --}}
                <aside class="flex min-h-[620px] min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 p-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-950">Current sale</h2>
                            <p class="mt-0.5 text-xs text-slate-500"><span x-text="cart.length"></span> product lines</p>
                        </div>
                        <div class="flex overflow-hidden rounded-lg border border-slate-200 text-xs font-bold">
                            <button type="button" x-on:click="switchType('pos')" x-bind:class="saleType === 'pos' ? 'bg-slate-950 text-white' : 'bg-white text-slate-600'" class="px-3 py-2">POS</button>
                            <button type="button" x-on:click="switchType('invoice')" x-bind:class="saleType === 'invoice' ? 'bg-slate-950 text-white' : 'bg-white text-slate-600'" class="px-3 py-2">Invoice</button>
                            <button type="button" x-on:click="switchType('quote')" x-bind:class="saleType === 'quote' ? 'bg-slate-950 text-white' : 'bg-white text-slate-600'" class="px-3 py-2">Quote</button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-4">
                        <div x-show="cart.length === 0" class="py-16 text-center text-sm text-slate-400">Scan or select a product to begin.</div>
                        <div class="space-y-3">
                            <template x-for="line in cart" :key="line.id">
                                <article class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1"><h3 class="truncate text-sm font-semibold" x-text="line.name"></h3><p class="mt-1 text-xs text-slate-500" x-text="money(line.unit_price_kobo)"></p></div>
                                        <button type="button" x-on:click="removeLine(line.id)" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600"><i data-lucide="x" class="h-4 w-4"></i></button>
                                    </div>
                                    <div class="mt-3 grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3">
                                        <div class="flex items-center overflow-hidden rounded-lg border border-slate-300">
                                            <button type="button" x-on:click="changeQuantity(line, -1)" class="h-8 w-8 hover:bg-slate-100">−</button>
                                            <input type="number" min="1" step="1" class="ec-pos-quantity-input" x-model.number="line.quantity" :max="line.track_inventory ? stockFor(line) : null" @input="setQuantity(line, $event.target.value)" @change="setQuantity(line, $event.target.value)" aria-label="Item quantity">
                                            <button type="button" x-on:click="changeQuantity(line, 1)" class="h-8 w-8 hover:bg-slate-100">+</button>
                                        </div>
                                        <input x-model.number="line.discount" type="number" min="0" step="0.01" placeholder="Discount ₦" class="min-h-8 min-w-0 rounded-lg border border-slate-300 px-2 text-sm">
                                        <strong class="text-sm" x-text="money((line.unit_price_kobo * line.quantity) - (Number(line.discount || 0) * 100))"></strong>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </div>

                    <div x-show="saleType !== 'quote'" class="border-t border-slate-200 p-4">
                        <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Split payments</h3><button type="button" x-on:click="addPayment" class="text-xs font-bold text-blue-700">Add payment</button></div>
                        <div class="space-y-2">
                            <template x-for="(payment, index) in payments" :key="index">
                                <div class="grid grid-cols-[minmax(0,1fr)_110px_34px] gap-2">
                                    <select x-model="payment.payment_method_id" class="min-h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-xs">
                                        <option value="">Select method</option>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method['id'] }}">{{ $method['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input x-model="payment.amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="min-h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-xs">
                                    <button type="button" x-on:click="removePayment(index)" class="rounded-lg border border-slate-200 text-red-600">×</button>
                                    <input x-model="payment.reference" class="col-span-3 min-h-9 rounded-lg border border-slate-300 px-2 text-xs" placeholder="Reference (optional)">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-slate-50 p-4">
                        {{-- Server-side errors --}}
                        @if ($errors->any())
                            <div class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                                <ul class="list-disc pl-4 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-slate-500"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
                            <div class="flex justify-between text-slate-500"><span>Discounts</span><span x-text="`− ${money(discountTotal)}`"></span></div>
                            <div class="flex justify-between text-lg font-black text-slate-950"><span>Total</span><span x-text="money(total)"></span></div>
                            <div x-show="saleType !== 'quote'" class="flex justify-between text-sm font-semibold" x-bind:class="balance > 0 ? 'text-red-600' : 'text-emerald-700'"><span>Balance</span><span x-text="money(balance)"></span></div>
                        </div>

                        <div id="customer-error" class="mt-2 hidden rounded-lg bg-red-50 p-3 text-sm text-red-700">
                            ⚠️ Please select a customer before submitting.
                        </div>

                        <textarea x-model="notes" rows="2" placeholder="Sale notes" class="mt-3 w-full rounded-lg border border-slate-300 p-2 text-sm"></textarea>
                        <div class="mt-3 grid grid-cols-[auto_1fr] gap-2">
                            <button type="button" x-on:click="clearCart" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700">Clear</button>

                            <button
                                type="button"
                                x-on:click="submitForm"
                                x-bind:disabled="submitting"
                                class="min-h-11 rounded-xl bg-slate-950 px-4 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
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
            </form>
        </div>
    </x-layout.app-shell>

    {{-- ALPINE COMPONENT – with dynamic cart & payment generation --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posSaleNew', (stockMap, priceMap, paymentMethods, initialBranch = '') => ({
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

                init() {
                    this.resetPayments();
                    this.$watch('branchId', () => this.repriceCart());
                },

                stockFor(product) {
                    if (!product.track_inventory) return null;
                    return Number(this.stockMap[`${this.branchId}|${product.id}`] || 0) / 1000;
                },

                branchPrice(product) {
                    const value = this.priceMap?.[`${this.branchId}|${product.id}`];
                    return value === undefined || value === null || value === ''
                        ? Number(product.default_price_kobo || 0)
                        : Number(value);
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
                        this.cart.push({ ...product, quantity: 1, discount: 0, unit_price_kobo: this.branchPrice(product) });
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
                    this.resetPayments();
                },

                get subtotal() {
                    return this.cart.reduce((sum, line) => sum + (line.unit_price_kobo * line.quantity), 0);
                },

                get discountTotal() {
                    return this.cart.reduce((sum, line) => sum + Math.max(0, Number(line.discount || 0) * 100), 0);
                },

                get total() {
                    return Math.max(0, this.subtotal - this.discountTotal);
                },

                get paidTotal() {
                    return this.payments.reduce((sum, payment) => sum + Math.max(0, Number(payment.amount || 0) * 100), 0);
                },

                get balance() {
                    return Math.max(0, this.total - this.paidTotal);
                },

                money(kobo) {
                    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 2 }).format(kobo / 100);
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
                    // Show spinner
                    this.submitting = true;

                    // Validate
                    const customerId = this.customerId;
                    const branchId = this.branchId;
                    const cartLength = this.cart.length;

                    if (!customerId || customerId === '') {
                        const errorEl = document.getElementById('customer-error');
                        if (errorEl) {
                            errorEl.classList.remove('hidden');
                            errorEl.textContent = '⚠️ Please select a customer before submitting.';
                            errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            alert('Please select a customer.');
                        }
                        this.submitting = false;
                        return;
                    }

                    if (!branchId || branchId === '') {
                        alert('Please select a branch.');
                        this.submitting = false;
                        return;
                    }

                    if (cartLength === 0) {
                        alert('Please add at least one product to the cart.');
                        this.submitting = false;
                        return;
                    }

                    // Hide any previous error
                    const errorEl = document.getElementById('customer-error');
                    if (errorEl) errorEl.classList.add('hidden');

                    const form = document.getElementById('pos-form');
                    if (!form) {
                        this.submitting = false;
                        alert('Form not found. Please refresh and try again.');
                        return;
                    }

                    // Remove any previous dynamic inputs
                    document.querySelectorAll('.dynamic-cart-input, .dynamic-payment-input').forEach(el => el.remove());

                    // Generate hidden inputs for cart items
                    this.cart.forEach((line, index) => {
                        const fields = {
                            'product_id': line.id,
                            'quantity': line.quantity,
                            'unit_price': (line.unit_price_kobo / 100).toFixed(2),
                            'discount': Number(line.discount || 0).toFixed(2)
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

                    // Generate hidden inputs for payments (only if not quote)
                    if (this.saleType !== 'quote') {
                        this.payments.forEach((payment, index) => {
                            const amount = parseFloat(payment.amount);
                            if (!payment.payment_method_id || isNaN(amount) || amount <= 0) return;

                            const fields = {
                                'payment_method_id': payment.payment_method_id,
                                'amount': amount.toFixed(2),
                                'reference': payment.reference || ''
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

                    // Submit the form
                    form.submit();
                },
            }));
        });
    </script>
</x-layout.app>