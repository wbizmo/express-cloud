<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierBillPayment extends Model
{
    use HasUlids;

    protected $table = 'supplier_bill_payments';

    /** @var list<string> */
    protected $fillable = [
        'supplier_bill_id',
        'payment_method_id',
        'recorded_by_account_id',
        'amount_kobo',
        'reference',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'paid_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SupplierBill, $this> */
    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
