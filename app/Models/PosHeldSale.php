<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PosHeldSale extends Model
{
    use HasUlids;

    protected $table = 'pos_held_sales';

    /** @var list<string> */
    protected $fillable = [
        'hold_token',
        'pos_shift_id',
        'customer_id',
        'held_by_account_id',
        'cart_payload',
        'estimated_total_kobo',
        'status',
        'held_at',
        'resumed_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_payload' => 'array',
            'estimated_total_kobo' => 'integer',
            'held_at' => 'immutable_datetime',
            'resumed_at' => 'immutable_datetime',
        ];
    }
}
