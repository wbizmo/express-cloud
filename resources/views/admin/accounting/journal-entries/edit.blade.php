<x-layout.app title="Edit Journal Entry | Express Cloud">
    <x-layout.app-shell
        page-title="Edit Journal Entry"
        page-description="Update draft journal entry #{{ $entry->journal_number }}"
    >
        <x-ui.card>
            <form method="POST" action="{{ route('admin.accounting.journal-entries.update', $entry) }}" class="space-y-6" x-data="journalEntryForm({{ json_encode($entry->lines->map(fn($l) => ['debit' => $l->debit_kobo, 'credit' => $l->credit_kobo, 'account_id' => $l->ledger_account_id])->toArray()) }})">
                @csrf
                @method('PATCH')

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input name="entry_date" label="Entry Date" type="date" :value="old('entry_date', $entry->entry_date->toDateString())" required />
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Accounting Period</span>
                        <select name="accounting_period_id" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="">Select period</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected(old('accounting_period_id', $entry->accounting_period_id) == $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-slate-700">Status</span>
                        <select name="status" required class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm">
                            <option value="draft" @selected(old('status', $entry->status) === 'draft')>Draft</option>
                            <option value="posted" @selected(old('status', $entry->status) === 'posted')>Posted</option>
                        </select>
                    </label>
                    <x-ui.input name="memo" label="Memo / Description" :value="old('memo', $entry->memo)" required />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700">Journal Lines</span>
                        <button type="button" @click="addLine()" class="text-sm text-blue-600 hover:text-blue-700">+ Add line</button>
                    </div>
                    <div class="ec-responsive-table overflow-x-auto">
                        <table class="w-full min-w-[600px] text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-2 py-2 text-left">Account</th>
                                    <th class="px-2 py-2 text-left">Debit (kobo)</th>
                                    <th class="px-2 py-2 text-left">Credit (kobo)</th>
                                    <th class="px-2 py-2 text-left">Description</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="index">
                                    <tr class="border-t border-slate-100">
                                        <td class="px-2 py-2">
                                            <select :name="'lines['+index+'][ledger_account_id]'" required class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                                <option value="">Select account</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}" :selected="line.account_id == '{{ $account->id }}'">{{ $account->code }} — {{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" :name="'lines['+index+'][debit_kobo]'" x-model="line.debit" min="0" step="1" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" :name="'lines['+index+'][credit_kobo]'" x-model="line.credit" min="0" step="1" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="text" :name="'lines['+index+'][description]'" placeholder="Optional" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            <button type="button" @click="lines.splice(index, 1)" class="text-sm text-red-600 hover:text-red-700" x-show="lines.length > 1">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-slate-200 font-semibold">
                                    <td class="px-2 py-2 text-right" colspan="1">Totals</td>
                                    <td class="px-2 py-2" x-text="totalDebit()"></td>
                                    <td class="px-2 py-2" x-text="totalCredit()"></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr x-show="totalDebit() !== totalCredit()" class="text-red-600">
                                    <td colspan="5" class="px-2 py-2 text-sm">Debits and credits must balance.</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="submit">Update Journal Entry</x-ui.button>
                    <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
            </form>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>

<script>
    function journalEntryForm(initialLines = null) {
        return {
            lines: initialLines || [{ debit: '', credit: '', account_id: '' }, { debit: '', credit: '', account_id: '' }],
            addLine() {
                this.lines.push({ debit: '', credit: '', account_id: '' });
            },
            totalDebit() {
                return this.lines.reduce((sum, line) => sum + (parseInt(line.debit) || 0), 0);
            },
            totalCredit() {
                return this.lines.reduce((sum, line) => sum + (parseInt(line.credit) || 0), 0);
            }
        };
    }
</script>
