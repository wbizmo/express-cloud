<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SalesDelivery extends Model
{
    use HasUlids;

    protected $table = 'sales_deliveries';

    /** @var list<string> */
    protected $fillable = [
        'delivery_number',
        'sale_id',
        'warehouse_id',
        'delivered_by_account_id',
        'status',
        'delivery_address',
        'notes',
        'dispatched_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
        ];
    }
}
