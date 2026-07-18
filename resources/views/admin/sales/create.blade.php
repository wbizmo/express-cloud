<x-layout.app title="New sale | Express Cloud">
    @php
        $catalog = $products->map(static fn ($product) => [
            'id' => (string) $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'track_inventory' => (bool) $product->track_inventory,
            'default_price_kobo' => (int) $product->default_price_kobo,
        ])->values();
    @endphp

    <x-layout.app-shell page-title="POS checkout" page-description="Barcode-first checkout, branch stock visibility, split payments, invoices and quotes.">
        <div
            x-data="posSale(@js($catalog), @js($productStocks), @js($paymentMethods), @js(old('branch_id', $branches->first()?->id ?? '')))"
            class="min-h-[calc(100vh-9rem)]"
        >
            <form x-ref="form" method="POST" action="{{ route('admin.sales.store') }}" x-on:submit.prevent="submit" class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,.65fr)]">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="sale_type" x-bind:value="saleType">
                <input type="hidden" name="branch_id" x-bind:value="branchId">
                <input type="hidden" name="customer_id" x-bind:value="customerId">
                <input type="hidden" name="notes" x-bind:value="notes">

                <template x-for="(line, index) in cart" :key="line.id">
                    <div>
                        <input type="hidden" x-bind:name="`items[${index}][product_id]`" x-bind:value="line.id">
                        <input type="hidden" x-bind:name="`items[${index}][quantity]`" x-bind:value="line.quantity">
                        <input type="hidden" x-bind:name="`items[${index}][unit_price]`" x-bind:value="(line.default_price_kobo / 100).toFixed(2)">
                        <input type="hidden" x-bind:name="`items[${index}][discount]`" x-bind:value="Number(line.discount || 0).toFixed(2)">
                    </div>
                </template>

                <template x-for="(payment, index) in payments" :key="index">
                    <div x-show="saleType !== 'quote'">
                        <input type="hidden" x-bind:name="`payments[${index}][payment_method_id]`" x-bind:value="payment.payment_method_id">
                        <input type="hidden" x-bind:name="`payments[${index}][amount]`" x-bind:value="payment.amount">
                        <input type="hidden" x-bind:name="`payments[${index}][reference]`" x-bind:value="payment.reference">
                    </div>
                </template>

                <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 p-4">
                        <div class="grid gap-3 lg:grid-cols-[minmax(180px,240px)_minmax(0,1fr)]">
                            <select x-model="branchId" required class="min-h-12 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold focus:border-slate-900 focus:ring-slate-900">
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->code }} · {{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"><i data-lucide="scan-barcode" class="h-5 w-5"></i></span>
                                <input x-ref="search" x-model="query" x-on:keydown.enter.prevent="handleSearchEnter" type="search" placeholder="Scan barcode or search product name / SKU" class="min-h-12 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-4 text-sm outline-none focus:border-slate-900 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                                <span x-show="scanMessage" x-text="scanMessage" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"></span>
                            </div>
                        </div>
                    </div>

                    <div class="max-h-[calc(100vh-15rem)] overflow-y-auto p-4">
                        <div x-show="!branchId" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Select a branch to load branch-specific availability.</div>
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-4">
                            <template x-for="product in filteredProducts" :key="product.id">
                                <button type="button" x-on:click="addProduct(product)" x-bind:disabled="!canAdd(product)" class="min-w-0 rounded-xl border border-slate-200 bg-white p-3 text-left transition hover:-translate-y-0.5 hover:border-slate-900 hover:shadow-md disabled:cursor-not-allowed disabled:bg-slate-100 disabled:opacity-55">
                                    <div class="mb-3 flex items-start justify-between gap-2">
                                        <span class="truncate text-[11px] font-bold uppercase tracking-wide text-slate-400" x-text="product.sku"></span>
                                        <span x-show="!product.track_inventory" class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Service</span>
                                    </div>
                                    <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-slate-900" x-text="product.name"></h3>
                                    <div class="mt-3 flex items-end justify-between gap-2">
                                        <strong class="text-sm text-slate-950" x-text="money(product.default_price_kobo)"></strong>
                                        <span class="text-right text-[11px] font-medium" x-bind:class="product.track_inventory && stockFor(product) <= 0 ? 'text-red-600' : 'text-slate-500'" x-text="product.track_inventory ? `${stockFor(product)} left` : 'Not tracked'"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </section>

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

                    <div class="border-b border-slate-100 p-4">
                        <input type="search" x-model="customerId" placeholder="Customer ID (optional / walk-in)" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-4">
                        <div x-show="cart.length === 0" class="py-16 text-center text-sm text-slate-400">Scan or select a product to begin.</div>
                        <div class="space-y-3">
                            <template x-for="line in cart" :key="line.id">
                                <article class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1"><h3 class="truncate text-sm font-semibold" x-text="line.name"></h3><p class="mt-1 text-xs text-slate-500" x-text="money(line.default_price_kobo)"></p></div>
                                        <button type="button" x-on:click="removeLine(line.id)" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600"><i data-lucide="x" class="h-4 w-4"></i></button>
                                    </div>
                                    <div class="mt-3 grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3">
                                        <div class="flex items-center overflow-hidden rounded-lg border border-slate-300">
                                            <button type="button" x-on:click="changeQuantity(line, -1)" class="h-8 w-8 hover:bg-slate-100">−</button>
                                            <span class="w-9 text-center text-sm font-semibold" x-text="line.quantity"></span>
                                            <button type="button" x-on:click="changeQuantity(line, 1)" class="h-8 w-8 hover:bg-slate-100">+</button>
                                        </div>
                                        <input x-model.number="line.discount" type="number" min="0" step="0.01" placeholder="Discount ₦" class="min-h-8 min-w-0 rounded-lg border border-slate-300 px-2 text-sm">
                                        <strong class="text-sm" x-text="money((line.default_price_kobo * line.quantity) - (Number(line.discount || 0) * 100))"></strong>
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
                                    <select x-model="payment.payment_method_id" class="min-h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-xs"><option value="">Method</option><template x-for="method in paymentMethods" :key="method.id"><option x-bind:value="method.id" x-text="method.name"></option></template></select>
                                    <input x-model="payment.amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="min-h-10 min-w-0 rounded-lg border border-slate-300 px-2 text-xs">
                                    <button type="button" x-on:click="removePayment(index)" class="rounded-lg border border-slate-200 text-red-600">×</button>
                                    <input x-model="payment.reference" class="col-span-3 min-h-9 rounded-lg border border-slate-300 px-2 text-xs" placeholder="Reference (optional)">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-slate-50 p-4">
                        <div class="space-y-2 text-sm"><div class="flex justify-between text-slate-500"><span>Subtotal</span><span x-text="money(subtotal)"></span></div><div class="flex justify-between text-slate-500"><span>Discounts</span><span x-text="`− ${money(discountTotal)}`"></span></div><div class="flex justify-between text-lg font-black text-slate-950"><span>Total</span><span x-text="money(total)"></span></div><div x-show="saleType !== 'quote'" class="flex justify-between text-sm font-semibold" x-bind:class="balance > 0 ? 'text-red-600' : 'text-emerald-700'"><span>Balance</span><span x-text="money(balance)"></span></div></div>
                        <textarea x-model="notes" rows="2" placeholder="Sale notes" class="mt-3 w-full rounded-lg border border-slate-300 p-2 text-sm"></textarea>
                        <div class="mt-3 grid grid-cols-[auto_1fr] gap-2"><button type="button" x-on:click="clearCart" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700">Clear</button><button type="submit" x-bind:disabled="submitting || !branchId || cart.length === 0" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40"><i x-show="submitting" data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i><span x-text="submitting ? 'Processing…' : (saleType === 'quote' ? 'Save quote' : saleType === 'invoice' ? 'Create invoice' : 'Complete sale')"></span></button></div>
                    </div>
                </aside>
            </form>
        </div>
    </x-layout.app-shell>
</x-layout.app>
