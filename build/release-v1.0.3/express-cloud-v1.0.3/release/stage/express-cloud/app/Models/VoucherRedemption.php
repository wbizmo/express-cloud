<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class VoucherRedemption extends Model
{
    use HasUlids;

    protected $fillable = [
        'discount_voucher_id',
        'sale_id',
        'customer_id',
        'redeemed_by_account_id',
        'discount_amount_kobo',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount_kobo' => 'integer',
            'redeemed_at' => 'immutable_datetime',
        ];
    }
}
