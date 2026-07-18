<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

final readonly class LisaBusinessContext
{
    /**
     * Build the authorised-scope business snapshot used by Lisa when
     * answering questions in the chat assistant.
     *
     * @return array{
     *     today: array{sales_count: int, sales_total_kobo: int, unpaid_total_kobo: int},
     *     inventory: array{low_stock_rows: int, zero_stock_rows: int},
     *     procurement: array{open_purchase_orders: int},
     * }
     */
    public function for(Account $account): array
    {
        $branchIds = $this->authorisedBranchIds($account);

        return [
            'today' => $this->todaySales($branchIds),
            'inventory' => $this->inventory($branchIds),
            'procurement' => $this->procurement($branchIds),
        ];
    }

    /** @return list<string>|null null means the account is authorised for every branch */
    private function authorisedBranchIds(Account $account): ?array
    {
        if ($account->is_allowed_all_branches) {
            return null;
        }

        return $account->branches()->pluck('branches.id')->all();
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{sales_count: int, sales_total_kobo: int, unpaid_total_kobo: int}
     */
    private function todaySales(?array $branchIds): array
    {
        $today = today()->toDateString();

        $sales = DB::table('sales')
            ->whereDate('sale_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds));

        return [
            'sales_count' => (int) (clone $sales)->count(),
            'sales_total_kobo' => (int) (clone $sales)->sum('grand_total_kobo'),
            'unpaid_total_kobo' => (int) (clone $sales)
                ->selectRaw('COALESCE(SUM(GREATEST(grand_total_kobo - paid_amount_kobo, 0)), 0) AS unpaid_total')
                ->value('unpaid_total'),
        ];
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{low_stock_rows: int, zero_stock_rows: int}
     */
    private function inventory(?array $branchIds): array
    {
        $stock = DB::table('product_branch_stock')
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds));

        return [
            'low_stock_rows' => (int) (clone $stock)
                ->whereColumn('quantity_milliunits', '<=', 'minimum_stock_milliunits')
                ->count(),
            'zero_stock_rows' => (int) (clone $stock)
                ->where('quantity_milliunits', '<=', 0)
                ->count(),
        ];
    }

    /**
     * @param  list<string>|null  $branchIds
     * @return array{open_purchase_orders: int}
     */
    private function procurement(?array $branchIds): array
    {
        $openPurchaseOrders = (int) DB::table('purchase_orders')
            ->whereNotIn('status', ['received', 'cancelled'])
            ->when($branchIds !== null, fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->count();

        return [
            'open_purchase_orders' => $openPurchaseOrders,
        ];
    }
}