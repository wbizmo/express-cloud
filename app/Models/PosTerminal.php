<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PosTerminal extends Model
{
    use HasUlids;

    protected $table = 'pos_terminals';

    /** @var list<string> */
    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'device_fingerprint_hash',
        'printer_profile',
        'status',
        'assigned_account_id',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'immutable_datetime',
        ];
    }
}
