<x-layout.app title="Accounting Reports | Express Cloud">
    <x-layout.app-shell
        page-title="Accounting Reports"
        page-description="Trial balance, profit & loss, balance sheet, cash flow, and the general ledger — all from the same posted journal data."
    >
        {{-- Report type toggle --}}
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($reportTypes as $key => $label)
                <a
                    href="{{ route('admin.accounting.reports.index', array_merge(request()->query(), ['report' => $key])) }}"
                    class="rounded-full px-4 py-2 text-sm font-medium {{ $report === $key ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <input type="hidden" name="report" value="{{ $report }}">

            @if ($report === 'balance-sheet')
                <x-ui.input name="to" type="date" label="As of" :value="$to" />
            @else
                <x-ui.input name="from" type="date" label="From" :value="$from" />
                <x-ui.input name="to" type="date" label="To" :value="$to" />
            @endif

            @if ($report === 'general-ledger')
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-slate-700">Ledger account</span>
                    <x-ui.searchable-select
                        name="account_id"
                        :options="$accounts->map(fn ($a) => ['value' => $a->id, 'label' => $a->code.' — '.$a->name])"
                        :selected="$accountId"
                        placeholder="Select account"
                    />
                </label>
            @endif

            <div class="flex items-end">
                <x-ui.button type="submit" class="w-full">Apply filters</x-ui.button>
            </div>
        </form>

        {{-- Export actions --}}
        @can('accounting.reports.export')
            <div class="mb-6 flex flex-wrap gap-3">
                @foreach (['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $formatLabel)
                    <a
                        href="{{ route('admin.accounting.reports.export', array_merge(request()->query(), ['report' => $report, 'format' => $format])) }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50"
                    >
                        Export {{ $formatLabel }}
                    </a>
                @endforeach
            </div>
        @endcan

        {{-- Trial Balance --}}
        @if ($report === 'trial-balance')
            <x-ui.card title="Trial Balance">
                <div class="ec-responsive-table overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Code</th>
                                <th class="px-3 py-3">Account</th>
                                <th class="px-3 py-3">Type</th>
                                <th class="px-3 py-3 text-right">Debit (₦)</th>
                                <th class="px-3 py-3 text-right">Credit (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['lines'] as $row)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-3 font-mono text-xs">{{ $row->code }}</td>
                                    <td class="px-3 py-3">{{ $row->name }}</td>
                                    <td class="px-3 py-3 capitalize text-slate-500">{{ $row->type }}</td>
                                    <td class="px-3 py-3 text-right">{{ number_format($row->debit_kobo / 100, 2) }}</td>
                                    <td class="px-3 py-3 text-right">{{ number_format($row->credit_kobo / 100, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-slate-500">No posted activity in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        {{-- Income Statement / P&L --}}
        @if ($report === 'income-statement')
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card title="Revenue">
                    <div class="space-y-2">
                        @forelse ($data['revenue'] as $row)
                            <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                                <span>{{ $row->name }}</span>
                                <span class="font-medium">₦{{ number_format(($row->credit_kobo - $row->debit_kobo) / 100, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No revenue posted in this range.</p>
                        @endforelse
                    </div>
                </x-ui.card>
                <x-ui.card title="Expenses">
                    <div class="space-y-2">
                        @forelse ($data['expense'] as $row)
                            <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                                <span>{{ $row->name }}</span>
                                <span class="font-medium">₦{{ number_format(($row->debit_kobo - $row->credit_kobo) / 100, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No expenses posted in this range.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-900 p-6 text-white">
                <div class="flex items-center justify-between text-sm text-slate-300">
                    <span>Total revenue</span><span>₦{{ number_format($data['total_revenue_kobo'] / 100, 2) }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-sm text-slate-300">
                    <span>Total expenses</span><span>₦{{ number_format($data['total_expense_kobo'] / 100, 2) }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-white/10 pt-3 text-lg font-semibold">
                    <span>Net {{ $data['net_profit_kobo'] >= 0 ? 'Profit' : 'Loss' }}</span>
                    <span>₦{{ number_format(abs($data['net_profit_kobo']) / 100, 2) }}</span>
                </div>
            </div>
        @endif

        {{-- Balance Sheet --}}
        @if ($report === 'balance-sheet')
            <div class="grid gap-6 lg:grid-cols-3">
                <x-ui.card title="Assets">
                    <div class="space-y-2">
                        @forelse ($data['assets'] as $row)
                            <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                                <span>{{ $row->name }}</span>
                                <span class="font-medium">₦{{ number_format(($row->debit_kobo - $row->credit_kobo) / 100, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No asset activity.</p>
                        @endforelse
                        <div class="flex justify-between pt-2 text-sm font-semibold">
                            <span>Total assets</span><span>₦{{ number_format($data['total_assets_kobo'] / 100, 2) }}</span>
                        </div>
                    </div>
                </x-ui.card>
                <x-ui.card title="Liabilities">
                    <div class="space-y-2">
                        @forelse ($data['liabilities'] as $row)
                            <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                                <span>{{ $row->name }}</span>
                                <span class="font-medium">₦{{ number_format(($row->credit_kobo - $row->debit_kobo) / 100, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No liability activity.</p>
                        @endforelse
                        <div class="flex justify-between pt-2 text-sm font-semibold">
                            <span>Total liabilities</span><span>₦{{ number_format($data['total_liabilities_kobo'] / 100, 2) }}</span>
                        </div>
                    </div>
                </x-ui.card>
                <x-ui.card title="Equity">
                    <div class="space-y-2">
                        @forelse ($data['equity'] as $row)
                            <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                                <span>{{ $row->name }}</span>
                                <span class="font-medium">₦{{ number_format(($row->credit_kobo - $row->debit_kobo) / 100, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No equity activity.</p>
                        @endforelse
                        <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                            <span>Retained earnings (cumulative)</span>
                            <span class="font-medium">₦{{ number_format($data['retained_earnings_kobo'] / 100, 2) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 text-sm font-semibold">
                            <span>Total equity</span><span>₦{{ number_format($data['total_equity_kobo'] / 100, 2) }}</span>
                        </div>
                    </div>
                </x-ui.card>
            </div>
            <p class="mt-4 text-xs text-slate-400">
                Retained earnings reflects cumulative net income since inception, as this system does not run a formal period-close — the same approach QuickBooks uses for an "unclosed" interim balance sheet.
            </p>
        @endif

        {{-- Cash Flow --}}
        @if ($report === 'cash-flow')
            <x-ui.card title="Cash Flow Summary">
                <div class="mb-4 grid grid-cols-3 gap-4 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs uppercase text-slate-500">Opening cash</div>
                        <div class="mt-1 text-lg font-semibold">₦{{ number_format($data['opening_kobo'] / 100, 2) }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs uppercase text-slate-500">Net movement</div>
                        <div class="mt-1 text-lg font-semibold">₦{{ number_format($data['net_movement_kobo'] / 100, 2) }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-900 p-3 text-white">
                        <div class="text-xs uppercase text-slate-300">Closing cash</div>
                        <div class="mt-1 text-lg font-semibold">₦{{ number_format($data['closing_kobo'] / 100, 2) }}</div>
                    </div>
                </div>
                <div class="space-y-2">
                    @forelse ($data['by_source'] as $row)
                        <div class="flex justify-between border-b border-slate-100 py-2 text-sm">
                            <span class="capitalize">{{ str_replace('_', ' ', $row->source_type ?? 'Uncategorised') }}</span>
                            <span class="font-medium">₦{{ number_format($row->net_kobo / 100, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No cash movement in this range.</p>
                    @endforelse
                </div>
            </x-ui.card>
        @endif

        {{-- General Ledger --}}
        @if ($report === 'general-ledger')
            <x-ui.card title="General Ledger">
                @if ($accountId === '')
                    <p class="text-sm text-slate-500">Select a ledger account above to see its posted entries.</p>
                @else
                    <div class="ec-responsive-table overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-3 py-3">Date</th>
                                    <th class="px-3 py-3">Journal #</th>
                                    <th class="px-3 py-3">Memo</th>
                                    <th class="px-3 py-3 text-right">Debit (₦)</th>
                                    <th class="px-3 py-3 text-right">Credit (₦)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['lines'] as $row)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-3 py-3">{{ $row->entry_date }}</td>
                                        <td class="px-3 py-3 font-mono text-xs">{{ $row->journal_number }}</td>
                                        <td class="px-3 py-3">{{ $row->description ?: $row->memo }}</td>
                                        <td class="px-3 py-3 text-right">{{ number_format($row->debit_kobo / 100, 2) }}</td>
                                        <td class="px-3 py-3 text-right">{{ number_format($row->credit_kobo / 100, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No entries for this account in this range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        @endif
    </x-layout.app-shell>
</x-layout.app>
