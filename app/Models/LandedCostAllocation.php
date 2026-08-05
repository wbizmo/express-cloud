<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LandedCostAllocation extends Model
{
    use HasUlids;

    protected $fillable = [
        'goods_receipt_id', 'goods_receipt_line_id', 'cost_type',
        'allocation_method', 'status', 'amount_kobo',
        'expense_ledger_account_id', 'created_by_account_id',
        'reversed_by_account_id', 'journal_entry_id',
        'reversal_journal_entry_id', 'allocated_at', 'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'allocated_at' => 'immutable_datetime',
            'reversed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<GoodsReceipt, $this> */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
