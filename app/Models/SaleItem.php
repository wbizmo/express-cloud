<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaleItem extends Model
{
    use HasUlids;

    protected $table = 'sale_items';

    /** @var list<string> */
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name_snapshot',
        'sku_snapshot',
        'track_inventory_snapshot',
        'quantity_milliunits',
        'unit_price_kobo',
        'discount_amount_kobo',
        'tax_amount_kobo',
        'line_total_kobo',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory_snapshot' => 'boolean',
            'quantity_milliunits' => 'integer',
            'unit_price_kobo' => 'integer',
            'discount_amount_kobo' => 'integer',
            'tax_amount_kobo' => 'integer',
            'line_total_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
