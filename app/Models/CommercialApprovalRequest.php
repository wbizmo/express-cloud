<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class CommercialApprovalRequest extends Model
{
    use HasUlids;

    protected $table = 'commercial_approval_requests';

    /** @var list<string> */
    protected $fillable = [
        'request_type',
        'subject_type',
        'subject_id',
        'branch_id',
        'requested_by_account_id',
        'decided_by_account_id',
        'requested_changes',
        'business_memo',
        'status',
        'decision_note',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_changes' => 'array',
            'decided_at' => 'immutable_datetime',
        ];
    }
}
