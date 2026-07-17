<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class JournalLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'journal_entry_id',
        'ledger_account_id',
        'branch_id',
        'customer_id',
        'supplier_id',
        'debit_kobo',
        'credit_kobo',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'debit_kobo' => 'integer',
            'credit_kobo' => 'integer',
        ];
    }
}
