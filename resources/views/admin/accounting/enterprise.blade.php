<x-layout.app title="Enterprise Finance | Express Cloud">
    <x-layout.app-shell page-title="Enterprise finance" page-description="Financial statements, subledger controls, treasury, bank reconciliation and period close in one workspace.">
        <form method="GET" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <x-ui.input name="from" type="date" label="From" :value="$from->toDateString()" />
            <x-ui.input name="as_of" type="date" label="As of" :value="$asOf->toDateString()" />
            <label class="block"><span class="mb-2 block text-sm font-medium text-slate-700">Branch</span><select name="branch_id" class="min-h-11 w-full rounded-lg border border-slate-300 px-3"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($branchId === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></label>
            <x-ui.button type="submit" class="self-end">Refresh statements</x-ui.button>
        </form>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.card title="Revenue"><p class="text-2xl font-bold">₦{{ number_format($profitAndLoss['revenue_kobo']/100, 2) }}</p></x-ui.card>
            <x-ui.card title="Expenses"><p class="text-2xl font-bold">₦{{ number_format($profitAndLoss['expense_kobo']/100, 2) }}</p></x-ui.card>
            <x-ui.card title="Profit"><p class="text-2xl font-bold">₦{{ number_format($profitAndLoss['profit_kobo']/100, 2) }}</p></x-ui.card>
            <x-ui.card title="Control difference"><p class="text-2xl font-bold {{ $control['total_difference_kobo'] ? 'text-red-700' : 'text-emerald-700' }}">₦{{ number_format($control['total_difference_kobo']/100, 2) }}</p></x-ui.card>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-ui.card title="Trial balance" description="Posted debits and credits by account through the selected date.">
                <div class="overflow-x-auto"><table class="w-full min-w-[650px] text-sm"><thead><tr class="border-b text-left text-xs uppercase text-slate-500"><th class="p-3">Code</th><th class="p-3">Account</th><th class="p-3 text-right">Debit</th><th class="p-3 text-right">Credit</th></tr></thead><tbody>@forelse($trialBalance as $row)<tr class="border-b"><td class="p-3 font-mono">{{ $row->code }}</td><td class="p-3">{{ $row->name }}</td><td class="p-3 text-right">{{ number_format($row->debit_kobo/100,2) }}</td><td class="p-3 text-right">{{ number_format($row->credit_kobo/100,2) }}</td></tr>@empty<tr><td colspan="4" class="p-8 text-center text-slate-500">No posted journal lines.</td></tr>@endforelse</tbody></table></div>
            </x-ui.card>
            <x-ui.card title="Statement controls">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">Assets</dt><dd class="font-semibold">₦{{ number_format($balanceSheet['assets_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Liabilities</dt><dd class="font-semibold">₦{{ number_format($balanceSheet['liabilities_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Equity</dt><dd class="font-semibold">₦{{ number_format($balanceSheet['equity_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Balance sheet difference</dt><dd class="font-semibold">{{ number_format($balanceSheet['difference_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Receivables control</dt><dd class="font-semibold">{{ number_format($control['accounts_receivable_difference_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Payables control</dt><dd class="font-semibold">{{ number_format($control['accounts_payable_difference_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Inventory control</dt><dd class="font-semibold">{{ number_format($control['inventory_difference_kobo']/100,2) }}</dd></div>
                    <div><dt class="text-slate-500">Cash-flow change</dt><dd class="font-semibold">{{ number_format($cashFlow['net_change_kobo']/100,2) }}</dd></div>
                </dl>
            </x-ui.card>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-ui.card title="Cash and bank books" description="Posted treasury activity for the selected reporting window.">
                <div class="grid gap-4 md:grid-cols-2">
                    <div><h3 class="mb-2 text-sm font-semibold">Cash book</h3><div class="max-h-64 overflow-auto"><table class="w-full text-xs"><thead><tr class="border-b"><th class="p-2 text-left">Date</th><th class="p-2 text-left">Journal</th><th class="p-2 text-right">Debit</th><th class="p-2 text-right">Credit</th></tr></thead><tbody>@forelse($cashBook as $row)<tr class="border-b"><td class="p-2">{{ $row->entry_date }}</td><td class="p-2 font-mono">{{ $row->journal_number }}</td><td class="p-2 text-right">{{ number_format($row->debit_kobo/100,2) }}</td><td class="p-2 text-right">{{ number_format($row->credit_kobo/100,2) }}</td></tr>@empty<tr><td colspan="4" class="p-4 text-center text-slate-500">No cash activity.</td></tr>@endforelse</tbody></table></div></div>
                    <div><h3 class="mb-2 text-sm font-semibold">Bank and clearing book</h3><div class="max-h-64 overflow-auto"><table class="w-full text-xs"><thead><tr class="border-b"><th class="p-2 text-left">Date</th><th class="p-2 text-left">Journal</th><th class="p-2 text-right">Debit</th><th class="p-2 text-right">Credit</th></tr></thead><tbody>@forelse($bankBook as $row)<tr class="border-b"><td class="p-2">{{ $row->entry_date }}</td><td class="p-2 font-mono">{{ $row->journal_number }}</td><td class="p-2 text-right">{{ number_format($row->debit_kobo/100,2) }}</td><td class="p-2 text-right">{{ number_format($row->credit_kobo/100,2) }}</td></tr>@empty<tr><td colspan="4" class="p-4 text-center text-slate-500">No bank activity.</td></tr>@endforelse</tbody></table></div></div>
                </div>
            </x-ui.card>
            <x-ui.card title="Tax and inventory controls">
                <dl class="grid grid-cols-2 gap-4 text-sm"><div><dt class="text-slate-500">Tax ledger lines</dt><dd class="text-xl font-semibold">{{ $taxLedger->count() }}</dd></div><div><dt class="text-slate-500">Valuation rows</dt><dd class="text-xl font-semibold">{{ $inventoryValuation->count() }}</dd></div><div><dt class="text-slate-500">Inventory value</dt><dd class="font-semibold">₦{{ number_format($inventoryValuation->sum('inventory_value_kobo')/100,2) }}</dd></div><div><dt class="text-slate-500">Available quantity</dt><dd class="font-semibold">{{ number_format(($inventoryValuation->sum('quantity_milliunits')-$inventoryValuation->sum('reserved_milliunits'))/1000,3) }}</dd></div></dl>
            </x-ui.card>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <x-ui.card title="Treasury transfer">
                <form method="POST" action="{{ route('admin.accounting.enterprise.treasury.transfer') }}" class="space-y-3">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                    <select name="source_treasury_account_id" required class="min-h-11 w-full rounded-lg border px-3"><option value="">Source account</option>@foreach($treasuryAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }} · {{ $account->currency }}</option>@endforeach</select>
                    <select name="destination_treasury_account_id" required class="min-h-11 w-full rounded-lg border px-3"><option value="">Destination account</option>@foreach($treasuryAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }} · {{ $account->currency }}</option>@endforeach</select>
                    <x-ui.input name="amount" type="number" step="0.01" min="0.01" label="Amount" required />
                    <x-ui.input name="reference" label="Reference" />
                    <label class="block"><span class="mb-2 block text-sm font-medium">Memo</span><textarea name="memo" required class="min-h-24 w-full rounded-lg border p-3"></textarea></label>
                    <x-ui.button type="submit" class="w-full">Post transfer</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card title="Bank statement import" description="Paste a JSON array of statement lines with transaction_date, description, debit_kobo or credit_kobo and running_balance_kobo.">
                <form method="POST" action="{{ route('admin.accounting.enterprise.bank-statements.import') }}" class="space-y-3">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}">
                    <select name="bank_account_id" required class="min-h-11 w-full rounded-lg border px-3"><option value="">Bank account</option>@foreach($bankAccounts as $bank)<option value="{{ $bank->id }}">{{ $bank->name }} · {{ $bank->bank_name }}</option>@endforeach</select>
                    <div class="grid grid-cols-2 gap-2"><x-ui.input name="starts_on" type="date" label="Start" required /><x-ui.input name="ends_on" type="date" label="End" required /></div>
                    <div class="grid grid-cols-2 gap-2"><x-ui.input name="opening_balance" type="number" step="0.01" label="Opening" required /><x-ui.input name="closing_balance" type="number" step="0.01" label="Closing" required /></div>
                    <textarea name="lines_json" required class="min-h-36 w-full rounded-lg border p-3 font-mono text-xs" placeholder='[{"transaction_date":"2026-08-05","description":"Deposit","credit_kobo":100000,"running_balance_kobo":100000}]'></textarea>
                    <x-ui.button type="submit" class="w-full">Import statement</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card title="Period close">
                <div class="space-y-3">@forelse($periods as $period)<form method="POST" action="{{ route('admin.accounting.enterprise.periods.close',$period) }}" class="rounded-lg border p-3">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::ulid() }}"><div class="flex items-center justify-between"><div><p class="font-semibold">{{ $period->name }}</p><p class="text-xs text-slate-500">{{ $period->starts_on?->toDateString() }} – {{ $period->ends_on?->toDateString() }}</p></div><x-ui.status-badge>{{ $period->status instanceof \BackedEnum ? $period->status->value : $period->status }}</x-ui.status-badge></div>@if(($period->status instanceof \BackedEnum ? $period->status->value : $period->status)==='open')<textarea name="notes" class="mt-3 min-h-16 w-full rounded-lg border p-2 text-sm" placeholder="Close notes"></textarea><x-ui.button type="submit" class="mt-2 w-full">Reconcile and lock</x-ui.button>@endif</form>@empty<p class="text-sm text-slate-500">No accounting periods exist.</p>@endforelse</div>
            </x-ui.card>
        </div>
    </x-layout.app-shell>
</x-layout.app>
