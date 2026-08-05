<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GoodsReceiptLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'goods_receipt_id', 'purchase_order_line_id', 'product_id',
        'product_variant_id', 'inventory_batch_id',
        'received_quantity_milliunits', 'accepted_quantity_milliunits',
        'quarantined_quantity_milliunits', 'unit_cost_kobo',
        'tax_kobo', 'line_total_kobo',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity_milliunits' => 'integer',
            'accepted_quantity_milliunits' => 'integer',
            'quarantined_quantity_milliunits' => 'integer',
            'unit_cost_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'line_total_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<GoodsReceipt, $this> */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }
}
