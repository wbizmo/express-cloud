<x-layout.app title="Chart of Accounts | Express Cloud">
    <x-layout.app-shell
        page-title="Chart of Accounts"
        page-description="Ledger accounts used to post and report on every financial transaction."
    >
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 flex items-center justify-between">
                <span>{{ session('status') }}</span>
                <button @click="show = false" class="text-emerald-700 hover:text-emerald-900">×</button>
            </div>
        @endif

        @if ($errors->any())
            <div x-data="{ show: true }" x-show="show" class="mb-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 flex items-center justify-between">
                <span>{{ $errors->first() }}</span>
                <button @click="show = false" class="text-red-700 hover:text-red-900">×</button>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1fr_420px] min-w-0">
            <div class="min-w-0">
                <x-ui.card title="Ledger accounts">
                    <div class="ec-responsive-table overflow-x-auto">
                        <table class="w-full min-w-[820px] text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-3 py-3">Code</th>
                                    <th class="px-3 py-3">Name</th>
                                    <th class="px-3 py-3">Type</th>
                                    <th class="px-3 py-3">Parent</th>
                                    <th class="px-3 py-3">Balance (kobo)</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3">Manual posting</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($accounts as $account)
                                    <tr class="border-t border-slate-100 align-top">
                                        <td class="px-3 py-3 font-mono text-xs">{{ $account->code }}</td>
                                        <td class="px-3 py-3">
                                            <div class="font-medium text-slate-950">{{ $account->name }}</div>
                                            @if ($account->is_system)
                                                <span class="text-xs text-slate-400">System account</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 capitalize">{{ $account->type->value }}</td>
                                        <td class="px-3 py-3 text-slate-500">
                                            {{ $account->parent?->name ?? '—' }}
                                        </td>
                                        <td class="px-3 py-3 font-mono text-xs {{ $account->balance_kobo > 0 ? 'text-emerald-700' : ($account->balance_kobo < 0 ? 'text-red-600' : 'text-slate-500') }}">
                                            {{ number_format($account->balance_kobo, 0) }}
                                        </td>
                                        <td class="px-3 py-3">
                                            <x-ui.status-badge :tone="$account->is_active ? 'success' : 'neutral'">
                                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                                            </x-ui.status-badge>
                                        </td>
                                        <td class="px-3 py-3">
                                            {{ $account->allow_manual_posting ? 'Allowed' : 'Blocked' }}
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <a href="{{ route('admin.accounting.chart-of-accounts.edit', $account) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-8 text-center text-sm text-slate-500">
                                            No ledger accounts configured yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $accounts->links() }}</div>
                </x-ui.card>
            </div>

            <div class="min-w-0">
                <x-ui.card title="Add ledger account">
                    <form method="POST" action="{{ route('admin.accounting.chart-of-accounts.store') }}" class="space-y-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input name="code" label="Account code" placeholder="e.g. 6200" :value="old('code')" required />
                            <label class="block">
                                <span class="mb-2 block text-sm font-medium text-slate-700">Type</span>
                                <select name="type" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                                    <option value="">Select type</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->value }}" @selected(old('type') == $type->value)>{{ ucfirst($type->value) }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <x-ui.input name="name" label="Account name" :value="old('name')" required />
                        <x-ui.input name="opening_balance_kobo" label="Opening Balance" type="number" step="1" :value="old('opening_balance_kobo', 0)" help="Positive = Debit, Negative = Credit" />

                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-slate-700">Parent account</span>
                            <select name="parent_id" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                                <option value="">No parent</option>
                                @foreach ($parentOptions as $parent)
                                    <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_control_account" value="1" @checked(old('is_control_account'))>
                            Control account (not directly postable)
                        </label>

                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="allow_manual_posting" value="1" @checked(old('allow_manual_posting', true))>
                            Allow manual journal posting
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-medium text-slate-700">Description</span>
                            <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm">{{ old('description') }}</textarea>
                        </label>

                        <x-ui.button type="submit">Create account</x-ui.button>
                    </form>
                </x-ui.card>
            </div>
        </div>
    </x-layout.app-shell>
</x-layout.app>
