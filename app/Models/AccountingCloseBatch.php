<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AccountingCloseBatch extends Model
{
    use HasUlids;

    protected $fillable = [
        'accounting_period_id', 'status', 'reconciliation_snapshot',
        'prepared_by_account_id', 'approved_by_account_id',
        'prepared_at', 'approved_at', 'locked_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'reconciliation_snapshot' => 'array',
            'prepared_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'locked_at' => 'immutable_datetime',
        ];
    }
}
