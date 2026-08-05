<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockReservation extends Model
{
    use HasUlids;

    protected $fillable = [
        'warehouse_id', 'product_id', 'product_variant_id', 'inventory_batch_id',
        'account_id', 'reference_type', 'reference_id', 'identity_hash', 'quantity_milliunits',
        'status', 'expires_at', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'expires_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
