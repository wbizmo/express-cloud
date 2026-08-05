<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ReleaseVerificationRun extends Model
{
    use HasUlids;

    protected $fillable = [
        'release_name', 'commit_sha', 'status', 'checks', 'artifact_path',
        'artifact_sha256', 'started_at', 'completed_at', 'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'checks' => 'array', 'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
