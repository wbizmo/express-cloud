<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseRequisitionLine extends Model
{
    use HasUlids;

    protected $fillable = [
        'purchase_requisition_id', 'product_id', 'product_variant_id',
        'requested_quantity_milliunits', 'approved_quantity_milliunits',
        'estimated_unit_cost_kobo', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity_milliunits' => 'integer',
            'approved_quantity_milliunits' => 'integer',
            'estimated_unit_cost_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<PurchaseRequisition, $this> */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }
}
