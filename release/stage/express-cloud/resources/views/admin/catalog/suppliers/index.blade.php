<x-layout.app title="Suppliers | Express Cloud">
    <x-layout.app-shell
        page-title="Suppliers"
        page-description="Maintain vendor records used by purchasing and stock intake."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_440px]">
            <x-ui.card title="Supplier directory">
                <div class="space-y-3">
                    @forelse ($suppliers as $supplier)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-semibold text-slate-950">{{ $supplier->company_name }}</h2>
                                        @if ($supplier->is_preferred)
                                            <x-ui.status-badge tone="success">Preferred</x-ui.status-badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $supplier->supplier_code }}</p>
                                </div>
                                <x-ui.status-badge :tone="$supplier->status->value === 'active' ? 'success' : 'neutral'">
                                    {{ ucfirst($supplier->status->value) }}
                                </x-ui.status-badge>
                            </div>
                            <div class="mt-3 grid gap-1 text-sm text-slate-500 sm:grid-cols-2">
                                <p>{{ $supplier->contact_person ?: 'No contact person' }}</p>
                                <p>{{ $supplier->phone ?: 'No phone number' }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No suppliers configured.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create supplier">
                <form method="POST" action="{{ route('admin.catalog.suppliers.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input name="supplier_code" label="Supplier code" required />
                        <x-ui.input name="company_name" label="Company name" required />
                    </div>
                    <x-ui.input name="contact_person" label="Contact person" />
                    <x-ui.input name="category" label="Supplier category" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input name="email" type="email" label="Email" />
                        <x-ui.input name="phone" label="Phone" />
                    </div>
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Address</span>
                        <textarea name="address" class="min-h-24 w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm"></textarea>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input name="payment_terms_days" type="number" label="Payment terms (days)" />
                        <x-ui.input name="lead_time_days" type="number" label="Lead time (days)" />
                    </div>
                    <x-ui.input name="credit_limit" type="number" step="0.01" label="Credit limit (₦)" />
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_preferred" value="1" class="rounded border-slate-300">
                        Preferred supplier
                    </label>
                    <x-ui.button type="submit" class="w-full">Create supplier</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
