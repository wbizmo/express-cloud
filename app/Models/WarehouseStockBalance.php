<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WarehouseStockBalance extends Model
{
    use HasUlids;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'inventory_batch_id',
        'condition',
        'identity_hash',
        'quantity_milliunits',
        'reserved_milliunits',
        'weighted_average_cost_kobo',
        'inventory_value_kobo',
        'version',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'reserved_milliunits' => 'integer',
            'weighted_average_cost_kobo' => 'integer',
            'inventory_value_kobo' => 'integer',
            'version' => 'integer',
            'last_movement_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return BelongsTo<InventoryBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function availableMilliunits(): int
    {
        return max(0, $this->quantity_milliunits - $this->reserved_milliunits);
    }
}
