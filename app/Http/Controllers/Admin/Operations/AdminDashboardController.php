<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\LowStockAlert;
use App\Services\Reports\StaffPerformanceReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class AdminDashboardController
{
    public function __construct(
        private StaffPerformanceReport $performance,
    ) {}

    public function __invoke(Request $request): View
    {
        $from = $request->date('from')?->toDateString()
            ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString()
            ?? today()->toDateString();
        $branchId = $request->filled('branch')
            ? $request->string('branch')->toString()
            : null;

        $sales = DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled'])
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'branch_id',
                    $branchId,
                ),
            );

        $salesByBranch = DB::table('sales')
            ->join(
                'branches',
                'branches.id',
                '=',
                'sales.branch_id',
            )
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereIn('sales.sale_type', ['invoice', 'pos'])
            ->whereNotIn('sales.status', ['cancelled'])
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'sales.branch_id',
                    $branchId,
                ),
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_kobo')
            ->select([
                'branches.id',
                'branches.name',
            ])
            ->selectRaw(
                'SUM(sales.grand_total_kobo) AS total_kobo',
            )
            ->get();

        $dailyTrend = DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled'])
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'branch_id',
                    $branchId,
                ),
            )
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->select('sale_date')
            ->selectRaw(
                'SUM(grand_total_kobo) AS total_kobo',
            )
            ->get();

        $paymentBreakdown = DB::table('payments')
            ->join(
                'sales',
                'sales.id',
                '=',
                'payments.sale_id',
            )
            ->join(
                'payment_methods',
                'payment_methods.id',
                '=',
                'payments.payment_method_id',
            )
            ->whereBetween(
                DB::raw('DATE(payments.paid_at)'),
                [$from, $to],
            )
            ->when(
                $branchId !== null,
                static fn ($query) => $query->where(
                    'sales.branch_id',
                    $branchId,
                ),
            )
            ->groupBy(
                'payment_methods.id',
                'payment_methods.name',
            )
            ->orderByDesc('total_kobo')
            ->select([
                'payment_methods.id',
                'payment_methods.name',
            ])
            ->selectRaw(
                'SUM(payments.amount_kobo) AS total_kobo',
            )
            ->get();

        $activeSessions = 0;

        if (Schema::hasTable('account_sessions')) {
            $sessionQuery = DB::table('account_sessions');

            if (
                Schema::hasColumn(
                    'account_sessions',
                    'ended_at',
                )
            ) {
                $sessionQuery->whereNull('ended_at');
            } elseif (
                Schema::hasColumn(
                    'account_sessions',
                    'revoked_at',
                )
            ) {
                $sessionQuery->whereNull('revoked_at');
            }

            $activeSessions = $sessionQuery->count();
        }

        return view('admin.dashboard.index', [
            'from' => $from,
            'to' => $to,
            'selectedBranch' => $branchId,
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalSalesKobo' => (int) (
                clone $sales
            )->sum('grand_total_kobo'),
            'salesCount' => (clone $sales)->count(),
            'openLowStockCount' => LowStockAlert::query()
                ->whereNull('resolved_at')
                ->count(),
            'activeSessionsCount' => $activeSessions,
            'openNotifications' => AdminNotification::query()
                ->whereNull('resolved_at')
                ->orderByDesc('occurred_at')
                ->limit(8)
                ->get(),
            'salesByBranch' => $salesByBranch,
            'dailyTrend' => $dailyTrend,
            'paymentBreakdown' => $paymentBreakdown,
            'staffPerformance' => $this->performance->run(
                $from,
                $to,
                $branchId,
            ),
        ]);
    }
}
