<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Accounting\FinancialPostingClassification;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FinancialPosting extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'source_type',
        'source_id',
        'source_event',
        'classification',
        'journal_entry_id',
        'operation_request_id',
        'reason_code',
        'details',
        'classified_at',
    ];

    protected function casts(): array
    {
        return [
            'classification' => FinancialPostingClassification::class,
            'details' => 'array',
            'classified_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<JournalEntry, $this> */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** @return BelongsTo<OperationRequest, $this> */
    public function operationRequest(): BelongsTo
    {
        return $this->belongsTo(OperationRequest::class);
    }
}
