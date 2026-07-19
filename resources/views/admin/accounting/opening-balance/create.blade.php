<x-layout.app title="Opening Balance | Express Cloud">
    <x-layout.app-shell
        page-title="Opening Balance"
        page-description="Set opening balances for all active ledger accounts."
    >
        <x-ui.card>
            <form method="POST" action="{{ route('admin.accounting.opening-balance.store') }}" class="space-y-6">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="entry_date" label="Entry Date" type="date" :value="old('entry_date', now()->toDateString())" required />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Accounting Period</span>
                        <select name="accounting_period_id" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">Select period</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected(old('accounting_period_id') == $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <x-ui.input name="memo" label="Memo" :value="old('memo', 'Opening balance entry')" required />

                <div class="overflow-x-auto">
                    <h3 class="mb-2 text-sm font-medium text-slate-700">Account Balances</h3>
                    <table class="w-full min-w-[600px] text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-2 py-2 text-left">Account</th>
                                <th class="px-2 py-2 text-left">Debit (kobo)</th>
                                <th class="px-2 py-2 text-left">Credit (kobo)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $account)
                                <tr class="border-t border-slate-100">
                                    <td class="px-2 py-2">{{ $account->code }} — {{ $account->name }}</td>
                                    <td class="px-2 py-2">
                                        <input type="number" name="balances[{{ $loop->index }}][debit_kobo]" min="0" step="1" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" value="0">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" name="balances[{{ $loop->index }}][credit_kobo]" min="0" step="1" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" value="0">
                                    </td>
                                    <input type="hidden" name="balances[{{ $loop->index }}][ledger_account_id]" value="{{ $account->id }}">
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="submit">Post Opening Balance</x-ui.button>
                    <a href="{{ route('admin.accounting.journal-entries.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
            </form>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
