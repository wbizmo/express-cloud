<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TreasuryMovement extends Model
{
    use HasUlids;

    protected $fillable = [
        'movement_number',
        'source_treasury_account_id',
        'destination_treasury_account_id',
        'branch_id',
        'created_by_account_id',
        'operation_request_id',
        'journal_entry_id',
        'movement_type',
        'amount_kobo',
        'reference',
        'memo',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
