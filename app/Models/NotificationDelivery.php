<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationDelivery extends Model
{
    use HasUlids;

    protected $fillable = [
        'daily_close_run_id', 'recipient', 'notification_type', 'status',
        'attempt_count', 'provider_message_id', 'last_error', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['attempt_count' => 'integer', 'sent_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<DailyCloseRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(DailyCloseRun::class, 'daily_close_run_id');
    }
}
