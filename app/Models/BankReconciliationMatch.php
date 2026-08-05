<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class BankReconciliationMatch extends Model
{
    use HasUlids;

    protected $fillable = [
        'bank_statement_line_id', 'journal_line_id',
        'matched_by_account_id', 'matched_amount_kobo', 'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_amount_kobo' => 'integer',
            'matched_at' => 'immutable_datetime',
        ];
    }
}
