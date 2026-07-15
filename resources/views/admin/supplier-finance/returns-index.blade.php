<x-layout.app title="Supplier returns | Express Cloud">
    <x-layout.app-shell
        page-title="Supplier returns"
        page-description="Return tracked stock to suppliers with an immutable inventory movement and mandatory reason."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_440px]">
            <x-ui.card title="Return history">
                <div class="space-y-3">
                    @forelse ($returns as $return)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="font-semibold text-slate-950">{{ $return->return_number }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $return->supplier?->company_name }} · {{ $return->branch?->name }}
                                    </p>
                                </div>
                                <x-ui.status-badge tone="success">
                                    {{ ucfirst($return->status->value) }}
                                </x-ui.status-badge>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-sm">
                                <span class="text-slate-500">{{ $return->reason }}</span>
                                <span class="font-semibold text-slate-950">
                                    ₦{{ number_format($return->total_kobo / 100, 2) }}
                                </span>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">
                            No supplier returns recorded.
                        </p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create supplier return">
                <form
                    method="POST"
                    action="{{ route('admin.supplier-finance.returns.store') }}"
                    class="space-y-4"
                    x-data="{ lines: [0] }"
                >
                    @csrf

                    <select name="supplier_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">
                                {{ $supplier->company_name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="branch_id" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">Select branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>

                    <select name="supplier_bill_id" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        <option value="">No linked supplier bill</option>
                        @foreach ($bills as $bill)
                            <option value="{{ $bill->id }}">{{ $bill->bill_number }}</option>
                        @endforeach
                    </select>

                    <x-ui.input name="reason" label="Return reason" required />

                    <template x-for="index in lines" :key="index">
                        <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                            <select :name="`lines[${index}][product_id]`" required class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                                <option value="">Select tracked product</option>
                                @foreach ($products as $product)
                                    <option
                                        value="{{ $product->id }}"
                                        data-cost-kobo="{{ $product->default_cost_price_kobo }}"
                                    >
                                        {{ $product->name }} — {{ $product->sku }}
                                    </option>
                                @endforeach
                            </select>
                            <input :name="`lines[${index}][quantity]`" required placeholder="Quantity" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                            <input :name="`lines[${index}][unit_cost_kobo]`" type="number" required placeholder="Unit cost in kobo" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                        </div>
                    </template>

                    <button
                        type="button"
                        x-on:click="lines.push(lines.length ? Math.max(...lines) + 1 : 0)"
                        class="text-sm font-semibold text-blue-700"
                    >
                        Add another return line
                    </button>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Reference note</span>
                        <textarea name="reference_note" required class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                    </label>

                    <x-ui.button type="submit" class="w-full">
                        Confirm supplier return
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
