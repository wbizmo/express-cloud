<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Procurement\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseOrder extends Model
{
    use HasUlids;

    protected $table = 'purchase_orders';

    /** @var list<string> */
    protected $fillable = [
        'order_number',
        'supplier_id',
        'branch_id',
        'created_by_account_id',
        'approved_by_account_id',
        'status',
        'expected_at',
        'subtotal_kobo',
        'tax_kobo',
        'total_kobo',
        'reference_note',
        'approved_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'expected_at' => 'immutable_date',
            'subtotal_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'total_kobo' => 'integer',
            'approved_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<PurchaseOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }
}
