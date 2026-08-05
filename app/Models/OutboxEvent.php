<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OutboxEvent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'outbox_events';

    /** @var list<string> */
    protected $fillable = [
        'operation_request_id',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'occurred_at',
        'published_at',
        'publish_attempts',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'publish_attempts' => 'integer',
        ];
    }

    /** @return BelongsTo<OperationRequest, $this> */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(OperationRequest::class, 'operation_request_id');
    }
}
