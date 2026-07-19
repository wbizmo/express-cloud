<x-layout.app title="Edit ledger account | Express Cloud">
    <x-layout.app-shell
        page-title="Edit ledger account"
        page-description="{{ $ledgerAccount->code }} — {{ ucfirst($ledgerAccount->type->value) }} account."
    >
        <x-ui.card title="Account details">
            <form
                method="POST"
                action="{{ route('admin.accounting.chart-of-accounts.update', $ledgerAccount) }}"
                class="max-w-xl space-y-4"
            >
                @csrf
                @method('PATCH')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="mb-2 block text-sm font-medium text-slate-700">Code</span>
                        <p class="font-mono text-sm text-slate-500">{{ $ledgerAccount->code }}</p>
                    </div>
                    <div>
                        <span class="mb-2 block text-sm font-medium text-slate-700">Type</span>
                        <p class="text-sm capitalize text-slate-500">{{ $ledgerAccount->type->value }}</p>
                    </div>
                </div>

                <x-ui.input
                    name="name"
                    label="Account name"
                    :value="old('name', $ledgerAccount->name)"
                    required
                />

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                    <textarea
                        name="description"
                        rows="3"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm"
                    >{{ old('description', $ledgerAccount->description) }}</textarea>
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ledgerAccount->is_active))>
                    Active
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="allow_manual_posting" value="1" @checked(old('allow_manual_posting', $ledgerAccount->allow_manual_posting))>
                    Allow manual journal posting
                </label>

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="submit">Save changes</x-ui.button>
                    <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="text-sm text-slate-500 hover:text-slate-700">
                        Cancel
                    </a>
                </div>
            </form>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
