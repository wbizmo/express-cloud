<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class InventoryValuationSnapshot extends Model
{
    use HasUlids;

    protected $fillable = [
        'snapshot_date', 'warehouse_id', 'product_id', 'product_variant_id',
        'inventory_batch_id', 'condition', 'identity_hash', 'quantity_milliunits',
        'weighted_average_cost_kobo', 'inventory_value_kobo', 'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'immutable_date',
            'quantity_milliunits' => 'integer',
            'weighted_average_cost_kobo' => 'integer',
            'inventory_value_kobo' => 'integer',
            'captured_at' => 'immutable_datetime',
        ];
    }
}
