<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BankStatementLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'bank_statement_import_id', 'transaction_date', 'reference',
        'description', 'debit_kobo', 'credit_kobo',
        'running_balance_kobo', 'status',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'immutable_date',
            'debit_kobo' => 'integer',
            'credit_kobo' => 'integer',
            'running_balance_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<BankStatementImport, $this> */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function amountKobo(): int
    {
        return max($this->debit_kobo, $this->credit_kobo);
    }
}
