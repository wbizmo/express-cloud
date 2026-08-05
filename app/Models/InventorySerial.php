<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventorySerial extends Model
{
    use HasUlids;

    protected $fillable = [
        'product_id', 'product_variant_id', 'inventory_batch_id',
        'warehouse_id', 'serial_number', 'status', 'reserved_at', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'immutable_datetime',
            'issued_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
