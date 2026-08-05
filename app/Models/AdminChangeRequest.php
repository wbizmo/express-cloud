<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AdminChangeRequest extends Model
{
    use HasUlids;

    protected $table = 'admin_change_requests';

    /** @var list<string> */
    protected $fillable = [
        'resource_type',
        'resource_id',
        'action',
        'payload',
        'requested_by_account_id',
        'decided_by_account_id',
        'status',
        'business_memo',
        'decision_note',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'decided_at' => 'immutable_datetime',
        ];
    }
}
