<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CashCounter extends Model
{
    use HasUlids;

    protected $fillable = [
        'branch_id',
        'treasury_account_id',
        'code',
        'name',
        'status',
    ];

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<TreasuryAccount, $this> */
    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    /** @return HasMany<CashCounterMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashCounterMovement::class);
    }
}
