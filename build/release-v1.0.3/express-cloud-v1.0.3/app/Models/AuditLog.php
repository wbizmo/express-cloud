<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    /** @var list<string> */
    protected $fillable = [
        'actor_account_id',
        'actor_name',
        'actor_role_snapshot',
        'action',
        'entity_type',
        'entity_id',
        'branch_id',
        'before_data',
        'after_data',
        'context',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
