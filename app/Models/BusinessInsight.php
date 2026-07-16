<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class BusinessInsight extends Model
{
    use HasUlids;

    protected $fillable = [
        'category', 'severity', 'title', 'summary', 'recommendation', 'evidence',
        'period_start', 'period_end', 'branch_id', 'generated_at', 'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'immutable_datetime',
            'dismissed_at' => 'immutable_datetime',
        ];
    }
}
