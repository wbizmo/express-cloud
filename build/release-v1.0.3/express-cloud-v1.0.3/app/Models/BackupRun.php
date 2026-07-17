<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class BackupRun extends Model
{
    use HasUlids;

    protected $fillable = [
        'backup_type',
        'status',
        'disk',
        'path',
        'checksum_sha256',
        'size_bytes',
        'manifest',
        'failure_message',
        'requested_by_account_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'manifest' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
