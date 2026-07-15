<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Commercial\SaleReturnStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SaleReturn extends Model
{
    use HasUlids;

    protected $fillable = [
        'return_code',
        'sale_id',
        'branch_id',
        'customer_id',
        'processed_by_account_id',
        'total_refund_kobo',
        'refund_method',
        'status',
        'reason',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'total_refund_kobo' => 'integer',
            'status' => SaleReturnStatus::class,
            'returned_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return HasMany<SaleReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
