<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockCountLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'stock_count_id', 'product_id', 'product_variant_id', 'inventory_batch_id',
        'condition', 'identity_hash', 'system_quantity_milliunits', 'counted_quantity_milliunits',
        'variance_milliunits', 'reason_code',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity_milliunits' => 'integer',
            'counted_quantity_milliunits' => 'integer',
            'variance_milliunits' => 'integer',
        ];
    }

    /** @return BelongsTo<StockCount, $this> */
    public function count(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }
}
