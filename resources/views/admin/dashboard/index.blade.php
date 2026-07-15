<x-layout.app title="Admin dashboard | Express Cloud">
    <x-layout.app-shell
        page-title="Admin dashboard"
        page-description="What needs your attention across every branch."
    >
        <form method="GET" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
            <x-ui.input name="from" type="date" label="From" :value="$from" />
            <x-ui.input name="to" type="date" label="To" :value="$to" />
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">Branch</span>
                <select name="branch" class="min-h-11 w-full rounded-lg border border-slate-300 px-3.5 text-sm">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($selectedBranch === $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end">
                <x-ui.button type="submit" class="w-full">Apply filters</x-ui.button>
            </div>
        </form>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Sales', 'value' => '₦'.number_format($totalSalesKobo / 100, 2)],
                ['label' => 'Transactions', 'value' => number_format($salesCount)],
                ['label' => 'Open low-stock alerts', 'value' => number_format($openLowStockCount)],
                ['label' => 'Active sessions', 'value' => number_format($activeSessionsCount)],
            ] as $metric)
                <x-ui.card>
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-2xl font-bold text-slate-950">{{ $metric['value'] }}</p>
                </x-ui.card>
            @endforeach
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-ui.card title="Sales by branch" description="Bar-chart data, ordered by revenue.">
                <div class="space-y-4">
                    @php($maxBranch = max(1, (int) $salesByBranch->max('total_kobo')))
                    @forelse ($salesByBranch as $row)
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $row->name }}</span>
                                <span class="font-semibold text-slate-950">₦{{ number_format(((int) $row->total_kobo) / 100, 2) }}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ max(3, (((int) $row->total_kobo) / $maxBranch) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No sales in this period.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Payment-method breakdown" description="Stacked comparison data without pie charts.">
                <div class="space-y-4">
                    @php($maxPayment = max(1, (int) $paymentBreakdown->max('total_kobo')))
                    @forelse ($paymentBreakdown as $row)
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $row->name }}</span>
                                <span class="font-semibold text-slate-950">₦{{ number_format(((int) $row->total_kobo) / 100, 2) }}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-slate-700" style="width: {{ max(3, (((int) $row->total_kobo) / $maxPayment) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No payment data in this period.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
            <x-ui.card title="Sales trend" description="Daily trend data for the selected date range.">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-3">Date</th>
                                <th class="px-3 py-3">Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($dailyTrend as $row)
                                <tr>
                                    <td class="px-3 py-4">{{ \Illuminate\Support\Carbon::parse($row->sale_date)->format('d M Y') }}</td>
                                    <td class="px-3 py-4 font-semibold">₦{{ number_format(((int) $row->total_kobo) / 100, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-3 py-10 text-center text-slate-500">No trend data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card title="Needs attention">
                <div class="space-y-3">
                    @forelse ($openNotifications as $notification)
                        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <h3 class="font-semibold text-slate-950">{{ $notification->title }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $notification->message }}</p>
                            <form method="POST" action="{{ route('admin.operations.notifications.read', $notification) }}" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <button class="text-sm font-semibold text-blue-700">Mark as read</button>
                            </form>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">Nothing requires immediate attention.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <x-ui.card title="Staff performance ranking" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[840px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-3">Rank</th>
                            <th class="px-3 py-3">Staff</th>
                            <th class="px-3 py-3">Sales</th>
                            <th class="px-3 py-3">Revenue</th>
                            <th class="px-3 py-3">Units</th>
                            <th class="px-3 py-3">Customers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($staffPerformance as $index => $row)
                            <tr>
                                <td class="px-3 py-4 font-semibold">{{ $index + 1 }}</td>
                                <td class="px-3 py-4">{{ trim($row->first_name.' '.$row->last_name) }}</td>
                                <td class="px-3 py-4">{{ $row->sales_count }}</td>
                                <td class="px-3 py-4 font-semibold">₦{{ number_format(((int) $row->revenue_kobo) / 100, 2) }}</td>
                                <td class="px-3 py-4">{{ app(\App\Services\Inventory\Quantity::class)->format((int) $row->units_milliunits) }}</td>
                                <td class="px-3 py-4">{{ $row->customers_served }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </x-layout.app-shell>
</x-layout.app>
