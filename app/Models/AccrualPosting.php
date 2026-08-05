<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccrualPosting extends Model
{
    use HasUlids;

    protected $fillable = [
        'accrual_schedule_id',
        'journal_entry_id',
        'period_number',
        'posting_date',
        'amount_kobo',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'posting_date' => 'immutable_date',
            'amount_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<AccrualSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AccrualSchedule::class, 'accrual_schedule_id');
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
