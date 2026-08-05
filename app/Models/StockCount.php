<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StockCount extends Model
{
    use HasUlids;

    protected $fillable = [
        'count_number',
        'warehouse_id',
        'opened_by_account_id',
        'approved_by_account_id',
        'operation_request_id',
        'status',
        'counted_at',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'counted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return HasMany<StockCountLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }
}
