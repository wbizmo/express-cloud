<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SaleReturnItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'quantity_milliunits',
        'refund_amount_kobo',
        'restock',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'refund_amount_kobo' => 'integer',
            'restock' => 'boolean',
        ];
    }
}
