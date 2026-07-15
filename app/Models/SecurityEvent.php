<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Authentication\SecurityEventType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SecurityEvent extends Model
{
    use HasUlids;

    public const string UPDATED_AT = '';

    protected $table = 'security_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_type',
        'actor_account_id',
        'subject_account_id',
        'session_identifier',
        'ip_address',
        'user_agent',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SecurityEventType::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
