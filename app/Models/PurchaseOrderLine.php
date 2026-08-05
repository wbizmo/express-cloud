<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseOrderLine extends Model
{
    use HasUlids;

    protected $table = 'purchase_order_lines';

    /** @var list<string> */
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_variant_id',
        'ordered_quantity_milliunits',
        'received_quantity_milliunits',
        'cancelled_quantity_milliunits',
        'backordered_quantity_milliunits',
        'unit_cost_kobo',
        'tax_rate_basis_points',
        'line_total_kobo',
        'landed_cost_allocated_kobo',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity_milliunits' => 'integer',
            'received_quantity_milliunits' => 'integer',
            'cancelled_quantity_milliunits' => 'integer',
            'backordered_quantity_milliunits' => 'integer',
            'unit_cost_kobo' => 'integer',
            'tax_rate_basis_points' => 'integer',
            'line_total_kobo' => 'integer',
            'landed_cost_allocated_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function remainingMilliunits(): int
    {
        return max(
            0,
            $this->ordered_quantity_milliunits
                - $this->received_quantity_milliunits
                - $this->cancelled_quantity_milliunits,
        );
    }
}
