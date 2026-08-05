<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PerformanceReview extends Model
{
    use HasUlids;

    protected $table = 'performance_reviews';

    /** @var list<string> */
    protected $fillable = [
        'employee_id',
        'reviewer_account_id',
        'period_starts_on',
        'period_ends_on',
        'score',
        'metrics',
        'summary',
        'development_plan',
        'status',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'period_starts_on' => 'immutable_date',
            'period_ends_on' => 'immutable_date',
            'score' => 'integer',
            'metrics' => 'array',
            'acknowledged_at' => 'immutable_datetime',
        ];
    }
}
