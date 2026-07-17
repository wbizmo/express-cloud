<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Accounting\JournalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JournalEntry extends Model
{
    use HasUlids;

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
        'posted_at',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'immutable_date',
            'status' => JournalStatus::class,
            'posted_at' => 'immutable_datetime',
            'reversed_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
