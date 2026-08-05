<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SalesDeliveryLine extends Model
{
    use HasUlids;

    protected $table = 'sales_delivery_lines';

    /** @var list<string> */
    protected $fillable = [
        'sales_delivery_id',
        'sale_item_id',
        'quantity_milliunits',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
        ];
    }
}
