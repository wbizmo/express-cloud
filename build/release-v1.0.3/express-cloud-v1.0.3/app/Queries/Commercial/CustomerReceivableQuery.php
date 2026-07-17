<?php

declare(strict_types=1);

namespace App\Queries\Commercial;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CustomerReceivableQuery
{
    /** @return LengthAwarePaginator<int, Customer> */
    public function customers(?string $search): LengthAwarePaginator
    {
        return Customer::query()
            ->select('customers.*')
            ->selectSub(
                DB::table('sales')
                    ->selectRaw(
                        'COALESCE(SUM(grand_total_kobo - paid_amount_kobo), 0)',
                    )
                    ->whereColumn('sales.customer_id', 'customers.id')
                    ->whereNotIn('sales.status', ['cancelled']),
                'outstanding_kobo',
            )
            ->when(
                $search !== null,
                static fn ($query) => $query->where(
                    static function ($nested) use ($search): void {
                        $nested
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    },
                ),
            )
            ->orderByDesc('outstanding_kobo')
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();
    }
}
