<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BusinessSnapshotEvidence extends Model
{
    use HasUlids;

    protected $table = 'business_snapshot_evidence';

    protected $fillable = [
        'business_snapshot_id', 'metric_key', 'source_table',
        'source_query_hash', 'value_payload', 'observed_at',
    ];

    protected function casts(): array
    {
        return ['value_payload' => 'array', 'observed_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<BusinessSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(BusinessSnapshot::class, 'business_snapshot_id');
    }
}
