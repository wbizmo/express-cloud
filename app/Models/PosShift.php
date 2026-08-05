<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PosShift extends Model
{
    use HasUlids;

    protected $table = 'pos_shifts';

    /** @var list<string> */
    protected $fillable = [
        'shift_number',
        'pos_terminal_id',
        'branch_id',
        'cashier_account_id',
        'closed_by_account_id',
        'status',
        'opening_float_kobo',
        'expected_cash_kobo',
        'declared_cash_kobo',
        'cash_variance_kobo',
        'opened_at',
        'closed_at',
        'closing_note',
    ];

    protected function casts(): array
    {
        return [
            'opening_float_kobo' => 'integer',
            'expected_cash_kobo' => 'integer',
            'declared_cash_kobo' => 'integer',
            'cash_variance_kobo' => 'integer',
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
