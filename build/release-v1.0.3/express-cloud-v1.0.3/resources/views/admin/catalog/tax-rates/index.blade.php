<x-layout.app title="Tax rates | Express Cloud">
    <x-layout.app-shell
        page-title="Tax rates"
        page-description="Manage reusable tax rates without hard-coding tax logic into products."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <x-ui.card title="Tax-rate directory">
                <div class="space-y-3">
                    @forelse ($records as $record)
                        <article class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-semibold text-slate-950">{{ $record->name }}</h2>
                                    @if ($record->is_default)
                                        <x-ui.status-badge tone="info">Default</x-ui.status-badge>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $record->products_count }} products</p>
                            </div>
                            <p class="text-lg font-semibold text-slate-950">{{ $record->percentage() }}%</p>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No tax rates configured.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Create tax rate">
                <form method="POST" action="{{ route('admin.catalog.tax-rates.store') }}" class="space-y-4">
                    @csrf
                    <x-ui.input name="name" label="Name" required />
                    <x-ui.input name="rate_percent" type="number" step="0.01" label="Rate (%)" required />
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300">
                        Set as default tax rate
                    </label>
                    <x-ui.button type="submit" class="w-full">Create tax rate</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
