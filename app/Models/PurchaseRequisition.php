<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseRequisition extends Model
{
    use HasUlids;

    protected $fillable = [
        'requisition_number',
        'branch_id',
        'warehouse_id',
        'requested_by_account_id',
        'approved_by_account_id',
        'operation_request_id',
        'status',
        'priority',
        'needed_on',
        'reason',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'needed_on' => 'immutable_date',
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<PurchaseRequisitionLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }
}
