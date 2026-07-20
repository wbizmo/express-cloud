<x-layout.app title="Payment methods | Express Cloud">
    <x-layout.app-shell
        page-title="Payment methods"
        page-description="Cash and Bank Transfer are protected defaults. One active method may be selected as the POS default."
    >
        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <x-ui.card title="Configured methods">
                <div class="space-y-3">
                    @foreach ($methods as $method)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-semibold text-slate-950">{{ $method->name }}</h2>
                                        @if ($method->is_system_default)
                                            <x-ui.status-badge tone="neutral">System</x-ui.status-badge>
                                        @endif
                                        @if ($method->is_default_for_pos)
                                            <x-ui.status-badge tone="success">POS default</x-ui.status-badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $method->bank_name ?: $method->description }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Ledger:
                                        @if ($method->ledgerAccount)
                                            <span class="font-mono">{{ $method->ledgerAccount->code }}</span> — {{ $method->ledgerAccount->name }}
                                        @else
                                            <span class="text-amber-600">Not linked — postings use the legacy fallback</span>
                                        @endif
                                    </p>
                                </div>
                                <x-ui.status-badge :tone="$method->is_active ? 'success' : 'neutral'">
                                    {{ $method->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.status-badge>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if (! $method->is_default_for_pos && $method->is_active)
                                    <form method="POST" action="{{ route('admin.payment-methods.default', $method) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-ui.button type="submit" variant="secondary">Set POS default</x-ui.button>
                                    </form>
                                @endif

                                @if (! $method->is_system_default)
                                    <form method="POST" action="{{ route('admin.payment-methods.toggle', $method) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-ui.button type="submit" variant="secondary">
                                            {{ $method->is_active ? 'Disable' : 'Enable' }}
                                        </x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $methods->links() }}
                </div>
            </x-ui.card>

            <x-ui.card title="Add payment method">
                <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="space-y-4">
                    @csrf
                    <x-ui.input name="name" label="Method name" required />
                    <x-ui.input name="bank_name" label="Bank name" />
                    <x-ui.input name="account_number" label="Account number" />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Link to ledger account</span>
                        <select name="ledger_account_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">Create a new ledger account instead</option>
                            @foreach ($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <x-ui.input
                        name="new_ledger_account_name"
                        label="New ledger account name (if not linking above)"
                        placeholder="e.g. Zenith Bank - Main"
                    />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                        <textarea name="description" class="min-h-24 w-full rounded-lg border border-slate-300 p-3 text-sm"></textarea>
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_default_for_pos" value="1" class="rounded border-slate-300">
                        Make default for POS
                    </label>
                    <x-ui.button type="submit" class="w-full">Add payment method</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
