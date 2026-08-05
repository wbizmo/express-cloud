<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PosCashMovement extends Model
{
    use HasUlids;

    protected $table = 'pos_cash_movements';

    /** @var list<string> */
    protected $fillable = [
        'pos_shift_id',
        'recorded_by_account_id',
        'movement_type',
        'amount_kobo',
        'memo',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
