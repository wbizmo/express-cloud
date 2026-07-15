<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PurchaseReceiptLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'purchase_receipt_id',
        'product_id',
        'quantity_milliunits',
        'unit_cost_kobo',
        'discount_kobo',
        'tax_kobo',
        'line_total_kobo',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'unit_cost_kobo' => 'integer',
            'discount_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'line_total_kobo' => 'integer',
        ];
    }
}
