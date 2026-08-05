<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class CustomerGroup extends Model
{
    use HasUlids;

    protected $table = 'customer_groups';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'default_payment_terms_days',
        'default_credit_limit_kobo',
        'price_group',
        'is_active',
        'created_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'default_payment_terms_days' => 'integer',
            'default_credit_limit_kobo' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
