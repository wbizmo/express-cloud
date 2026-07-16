<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupplierFinance\SupplierBillStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupplierBill extends Model
{
    use HasUlids;

    protected $table = 'supplier_bills';

    /** @var list<string> */
    protected $fillable = [
        'bill_number',
        'supplier_reference',
        'supplier_id',
        'branch_id',
        'purchase_order_id',
        'created_by_account_id',
        'bill_date',
        'due_date',
        'subtotal_kobo',
        'tax_kobo',
        'total_kobo',
        'paid_kobo',
        'status',
        'reference_note',
        'posted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'immutable_date',
            'due_date' => 'immutable_date',
            'subtotal_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'total_kobo' => 'integer',
            'paid_kobo' => 'integer',
            'status' => SupplierBillStatus::class,
            'posted_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
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

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return HasMany<SupplierBillLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(SupplierBillLine::class);
    }

    /** @return HasMany<SupplierBillPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierBillPayment::class);
    }

    /** @return HasMany<SupplierDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(
            SupplierDocument::class,
            'supplier_bill_id',
        );
    }

    public function balanceDueKobo(): int
    {
        return max(0, $this->total_kobo - $this->paid_kobo);
    }
}
