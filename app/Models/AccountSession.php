<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AccountSession extends Model
{
    use HasUlids;

    protected $table = 'account_sessions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'session_identifier',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
