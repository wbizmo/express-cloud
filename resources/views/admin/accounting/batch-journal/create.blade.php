<x-layout.app title="Batch Journal Entry | Express Cloud">
    <x-layout.app-shell
        page-title="Batch Journal Entry"
        page-description="Create multiple journal entries at once."
    >
        <x-ui.card>
            <form method="POST" action="{{ route('admin.accounting.batch-journal.store') }}" class="space-y-6" x-data="batchJournalForm()">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">

                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-slate-700">Journal Entries</h3>
                    <button type="button" @click="addEntry()" class="text-sm text-blue-600 hover:text-blue-700">+ Add Entry</button>
                </div>

                <template x-for="(entry, entryIndex) in entries" :key="entryIndex">
                    <div class="rounded-lg border border-slate-200 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium" x-text="'Entry #' + (entryIndex + 1)"></h4>
                            <button type="button" @click="entries.splice(entryIndex, 1)" class="text-sm text-red-600 hover:text-red-700" x-show="entries.length > 1">Remove</button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Date</label>
                                <input type="date" :name="'entries['+entryIndex+'][entry_date]'" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Period</label>
                                <select :name="'entries['+entryIndex+'][accounting_period_id]'" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">Select period</option>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Status</label>
                                <select :name="'entries['+entryIndex+'][status]'" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Memo</label>
                                <input type="text" :name="'entries['+entryIndex+'][memo]'" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Lines</span>
                                <button type="button" @click="addLine(entryIndex)" class="text-xs text-blue-600 hover:text-blue-700">+ Add line</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[500px] text-sm">
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
                                        <template x-for="(line, lineIndex) in entry.lines" :key="lineIndex">
                                            <tr class="border-t border-slate-100">
                                                <td class="px-2 py-2">
                                                    <select :name="'entries['+entryIndex+'][lines]['+lineIndex+'][ledger_account_id]'" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2 text-sm">
                                                        <option value="">Select account</option>
                                                        @foreach ($accounts as $account)
                                                            <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-2 py-2">
                                                    <input type="number" :name="'entries['+entryIndex+'][lines]['+lineIndex+'][debit_kobo]'" min="0" step="1" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                                </td>
                                                <td class="px-2 py-2">
                                                    <input type="number" :name="'entries['+entryIndex+'][lines]['+lineIndex+'][credit_kobo]'" min="0" step="1" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                                </td>
                                                <td class="px-2 py-2">
                                                    <input type="text" :name="'entries['+entryIndex+'][lines]['+lineIndex+'][description]'" placeholder="Optional" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                                </td>
                                                <td class="px-2 py-2 text-right">
                                                    <button type="button" @click="entry.lines.splice(lineIndex, 1)" class="text-sm text-red-600 hover:text-red-700" x-show="entry.lines.length > 2">Remove</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="submit">Post Batch</x-ui.button>
                    <a href="{{ route('admin.accounting.journal-entries.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
            </form>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>

<script>
    function batchJournalForm() {
        return {
            entries: [
                {
                    lines: [
                        { debit: '', credit: '' },
                        { debit: '', credit: '' }
                    ]
                }
            ],
            addEntry() {
                this.entries.push({
                    lines: [
                        { debit: '', credit: '' },
                        { debit: '', credit: '' }
                    ]
                });
            },
            addLine(entryIndex) {
                this.entries[entryIndex].lines.push({ debit: '', credit: '' });
            }
        };
    }
</script>
