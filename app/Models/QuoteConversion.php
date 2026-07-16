<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class QuoteConversion extends Model
{
    use HasUlids;

    protected $fillable = [
        'source_quote_id',
        'converted_sale_id',
        'converted_by_account_id',
        'target_type',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'immutable_datetime',
        ];
    }
}
