<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GoodsReceipt extends Model
{
    use HasUlids;

    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'purchase_receipt_id',
        'warehouse_id',
        'received_by_account_id',
        'operation_request_id',
        'supplier_reference',
        'status',
        'subtotal_kobo',
        'tax_kobo',
        'total_kobo',
        'received_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'total_kobo' => 'integer',
            'received_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return HasMany<GoodsReceiptLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }
}
