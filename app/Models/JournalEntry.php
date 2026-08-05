<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JournalEntry extends Model
{
    use HasUlids;

    protected $table = 'journal_entries';

    protected $fillable = [
        'journal_number',
        'entry_date',
        'accounting_period_id',
        'branch_id',
        'source_type',
        'source_id',
        'source_event',
        'status',
        'memo',
        'created_by_account_id',
        'reversal_of_entry_id',
        'operation_request_id',
        'operation_sequence',
        'posted_at',
        'reversed_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'operation_sequence' => 'integer',
    ];

    // ✅ Fixed: relationship to accounting period
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function createdByAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_entry_id');
    }

    public function reversedBy(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_entry_id');
    }
}
