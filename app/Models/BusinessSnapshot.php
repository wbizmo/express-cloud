<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BusinessSnapshot extends Model
{
    use HasUlids;

    protected $fillable = [
        'cache_key', 'company_key', 'branch_scope_hash', 'permission_scope_hash',
        'period_key', 'metric_version', 'payload', 'evidence_hash',
        'generated_at', 'stale_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'generated_at' => 'immutable_datetime',
            'stale_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<BusinessSnapshotEvidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(BusinessSnapshotEvidence::class, 'business_snapshot_id');
    }
}
