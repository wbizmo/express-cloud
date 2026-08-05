<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class LandedCostAllocation extends Model
{
    use HasUlids;

    protected $fillable = [
        'goods_receipt_id', 'goods_receipt_line_id', 'cost_type',
        'allocation_method', 'amount_kobo', 'expense_ledger_account_id',
        'created_by_account_id', 'journal_entry_id', 'allocated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'allocated_at' => 'immutable_datetime',
        ];
    }
}
