<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class DataBackfillRun extends Model
{
    use HasUlids;

    protected $fillable = [
        'source_label', 'status', 'checkpoint', 'counts', 'started_at',
        'completed_at', 'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'checkpoint' => 'array', 'counts' => 'array',
            'started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime',
        ];
    }
}
