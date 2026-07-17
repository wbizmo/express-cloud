<x-layout.app title="Accounting reports | Express Cloud">
<x-layout.app-shell page-title="Accounting and financial reports" page-description="General ledger, trial balance, profit and loss, and balance sheet are derived from posted journals.">
<div data-page-header class="mb-5"></div>
<form method="GET" class="mb-5 grid gap-4 sm:grid-cols-3">
<x-ui.input name="from" type="date" label="From" :value="$from" />
<x-ui.input name="to" type="date" label="To" :value="$to" />
<div class="flex items-end"><x-ui.button type="submit">Run reports</x-ui.button></div>
</form>
<x-ui.card title="Trial balance">
<div class="ec-responsive-table overflow-x-auto">
<table class="w-full min-w-[720px] text-left text-sm">
<thead><tr><th class="px-3 py-3">Code</th><th class="px-3 py-3">Account</th><th class="px-3 py-3 text-right">Debit</th><th class="px-3 py-3 text-right">Credit</th></tr></thead>
<tbody>
@php($totalDebit = 0)
@php($totalCredit = 0)
@foreach($trial as $row)
@php($totalDebit += (int) $row->debit_kobo)
@php($totalCredit += (int) $row->credit_kobo)
<tr class="border-t border-slate-100"><td class="px-3 py-3 font-mono">{{ $row->code }}</td><td class="px-3 py-3">{{ $row->name }}</td><td class="px-3 py-3 text-right">₦{{ number_format(((int)$row->debit_kobo)/100,2) }}</td><td class="px-3 py-3 text-right">₦{{ number_format(((int)$row->credit_kobo)/100,2) }}</td></tr>
@endforeach
</tbody>
<tfoot><tr class="border-t-2 border-slate-300 font-semibold"><td colspan="2" class="px-3 py-3">Totals</td><td class="px-3 py-3 text-right">₦{{ number_format($totalDebit/100,2) }}</td><td class="px-3 py-3 text-right">₦{{ number_format($totalCredit/100,2) }}</td></tr></tfoot>
</table>
</div>
</x-ui.card>
</x-layout.app-shell>
</x-layout.app>
