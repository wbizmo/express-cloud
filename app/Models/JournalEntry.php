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
        'book_type',
        'memo',
        'created_by_account_id',
        'reversal_of_entry_id',
        'accounting_close_batch_id',
        'locked_by_account_id',
        'locked_at',
        'operation_request_id',
        'operation_sequence',
        'posted_at',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'immutable_date',
            'posted_at' => 'immutable_datetime',
            'reversed_at' => 'immutable_datetime',
            'locked_at' => 'immutable_datetime',
            'operation_sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<AccountingPeriod, $this> */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function createdByAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by_account_id');
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /** @return BelongsTo<self, $this> */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_entry_id');
    }

    /** @return HasMany<self, $this> */
    public function reversedBy(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_entry_id');
    }

    /** @return BelongsTo<AccountingCloseBatch, $this> */
    public function closeBatch(): BelongsTo
    {
        return $this->belongsTo(AccountingCloseBatch::class, 'accounting_close_batch_id');
    }
}
