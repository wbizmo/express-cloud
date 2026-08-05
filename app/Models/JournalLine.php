<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JournalLine extends Model
{
    use HasUlids;

    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_entry_id',
        'ledger_account_id',
        'branch_id',
        'warehouse_id',
        'customer_id',
        'supplier_id',
        'tax_rate_id',
        'tax_basis_kobo',
        'tax_amount_kobo',
        'due_on',
        'subledger_reference',
        'debit_kobo',
        'credit_kobo',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'tax_basis_kobo' => 'integer',
            'tax_amount_kobo' => 'integer',
            'due_on' => 'immutable_date',
            'debit_kobo' => 'integer',
            'credit_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return BelongsTo<LedgerAccount, $this> */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }
}
