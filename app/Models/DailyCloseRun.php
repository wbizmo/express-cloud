<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DailyCloseRun extends Model
{
    use HasUlids;

    protected $fillable = [
        'business_date', 'status', 'attempt_count', 'lock_token', 'summary',
        'failure_step', 'failure_message', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'immutable_date', 'attempt_count' => 'integer',
            'summary' => 'array', 'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<NotificationDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }
}
