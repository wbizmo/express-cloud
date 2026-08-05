<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\BusinessInsight;
use Illuminate\Support\Facades\DB;

final class LisaInsightEngine
{
    public function generate(string $from, string $to): int
    {
        $generated = 0;

        $currentRevenue = (int) DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereIn('sale_type', ['invoice', 'pos'])
            ->whereNotIn('status', ['cancelled'])
            ->sum('grand_total_kobo');

        $discounts = (int) DB::table('sales')
            ->whereBetween('sale_date', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->sum('discount_amount_kobo');

        if ($currentRevenue > 0 && $discounts > (int) round($currentRevenue * 0.08)) {
            $generated += $this->store([
                'category' => 'sales',
                'severity' => 'warning',
                'title' => 'Discount pressure is reducing revenue quality',
                'summary' => 'Discounts exceeded 8% of recorded revenue during the selected period.',
                'recommendation' => 'Review high-discount sales, voucher usage and staff override patterns before approving additional discounts.',
                'evidence' => ['revenue_kobo' => $currentRevenue, 'discount_kobo' => $discounts],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        $lowStock = DB::table('product_branch_stock')
            ->whereColumn('quantity_milliunits', '<=', 'minimum_stock_milliunits')
            ->count();

        if ($lowStock > 0) {
            $generated += $this->store([
                'category' => 'inventory',
                'severity' => $lowStock >= 20 ? 'critical' : 'warning',
                'title' => 'Products require replenishment attention',
                'summary' => sprintf('%d branch-product records are at or below their reorder level.', $lowStock),
                'recommendation' => 'Open the low-stock report, prioritize fast-moving items and create purchase orders or stock transfers.',
                'evidence' => ['low_stock_records' => $lowStock],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        // ✅ FIX: replaced GREATEST with CASE for SQLite compatibility
        $outstanding = (int) DB::table('sales')
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('COALESCE(SUM(CASE WHEN grand_total_kobo - paid_amount_kobo > 0 THEN grand_total_kobo - paid_amount_kobo ELSE 0 END), 0) AS outstanding_total')
            ->value('outstanding_total');

        if ($outstanding > 0) {
            $generated += $this->store([
                'category' => 'customers',
                'severity' => 'info',
                'title' => 'Customer receivables require follow-up',
                'summary' => 'The business has unpaid or partially paid sales in its receivables ledger.',
                'recommendation' => 'Review customer balances and record settlements against the original sales as payments arrive.',
                'evidence' => ['outstanding_kobo' => $outstanding],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        if ($generated === 0) {
            $generated += $this->store([
                'category' => 'executive',
                'severity' => 'info',
                'title' => 'No material operational exception detected',
                'summary' => 'Lisa did not find a threshold breach in sales discounts, low stock or receivables for the selected period.',
                'recommendation' => 'Continue monitoring branch performance and review detailed reports for context.',
                'evidence' => [],
                'period_start' => $from,
                'period_end' => $to,
            ]);
        }

        return $generated;
    }

    /** @param array<string, mixed> $data */
    private function store(array $data): int
    {
        BusinessInsight::query()->updateOrCreate(
            [
                'category' => $data['category'],
                'branch_id' => $data['branch_id'] ?? null,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'title' => $data['title'],
            ],
            [...$data, 'generated_at' => now(), 'dismissed_at' => null],
        );

        return 1;
    }
}
