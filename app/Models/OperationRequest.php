<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Operations\OperationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OperationRequest extends Model
{
    use HasUlids;

    protected $table = 'operation_requests';

    /** @var list<string> */
    protected $fillable = [
        'scope',
        'idempotency_key',
        'request_fingerprint',
        'account_id',
        'branch_id',
        'status',
        'attempts',
        'result_type',
        'result_id',
        'response_payload',
        'failure_code',
        'failure_message',
        'started_at',
        'completed_at',
        'failed_at',
        'lease_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OperationStatus::class,
            'attempts' => 'integer',
            'response_payload' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'lease_expires_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<OutboxEvent, $this> */
    public function outboxEvents(): HasMany
    {
        return $this->hasMany(OutboxEvent::class);
    }
}
