<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CashCounterMovement extends Model
{
    use HasUlids;

    protected $fillable = [
        'cash_counter_id',
        'treasury_movement_id',
        'recorded_by_account_id',
        'movement_type',
        'amount_kobo',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<CashCounter, $this> */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(CashCounter::class, 'cash_counter_id');
    }

    /** @return BelongsTo<TreasuryMovement, $this> */
    public function treasuryMovement(): BelongsTo
    {
        return $this->belongsTo(TreasuryMovement::class);
    }
}
