<x-layout.app title="Customers | Express Cloud">
    <x-layout.app-shell
        page-title="Customers"
        page-description="Customer profiles, credit limits, balances, and sales relationships."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <x-ui.card title="Customer directory">
                <form method="GET" class="mb-5">
                    <x-ui.input
                        name="search"
                        label="Search customers"
                        :value="request('search')"
                        placeholder="Name, phone, or customer code"
                    />
                </form>

                <div class="space-y-3">
                    @forelse ($customers as $customer)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="font-semibold text-slate-950">{{ $customer->name }}</h2>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $customer->customer_code }}</p>
                                </div>
                                @if ($customer->is_wholesale)
                                    <x-ui.status-badge tone="info">Wholesale</x-ui.status-badge>
                                @endif
                            </div>
                            <div class="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                                <p>{{ $customer->phone }}</p>
                                <p>Balance: ₦{{ number_format($customer->balance_kobo / 100, 2) }}</p>
                                <p>Available credit: ₦{{ number_format($customer->availableCreditKobo() / 100, 2) }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No customers found.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Quick-add customer">
                <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-4">
                    @csrf
                    <x-ui.input name="name" label="Customer name" required />
                    <x-ui.input name="phone" label="Phone number" required />
                    <x-ui.input name="email" type="email" label="Email" />
                    <x-ui.input name="credit_limit" type="number" step="0.01" label="Credit limit (₦)" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Address</span>
                        <textarea name="address" class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_wholesale" value="1" class="rounded border-slate-300">
                        Wholesale customer
                    </label>
                    <x-ui.button type="submit" class="w-full">Create customer</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
