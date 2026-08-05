<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductVariant extends Model
{
    use HasUlids;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'name', 'attributes',
        'price_delta_kobo', 'cost_delta_kobo', 'status',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price_delta_kobo' => 'integer',
            'cost_delta_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
