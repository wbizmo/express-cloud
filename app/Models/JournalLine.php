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
        'customer_id',
        'supplier_id',
        'debit_kobo',
        'credit_kobo',
        'description',
    ];

    protected $casts = [
        'debit_kobo' => 'integer',
        'credit_kobo' => 'integer',
    ];

    // ✅ Relationship to journal entry
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    // ✅ The missing relationship that caused the error
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
