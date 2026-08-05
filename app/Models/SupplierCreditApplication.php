<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierCreditApplication extends Model
{
    use HasUlids;

    protected $fillable = [
        'supplier_credit_note_id',
        'supplier_bill_id',
        'applied_by_account_id',
        'amount_kobo',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'applied_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<SupplierCreditNote, $this> */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(SupplierCreditNote::class, 'supplier_credit_note_id');
    }

    /** @return BelongsTo<SupplierBill, $this> */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class, 'supplier_bill_id');
    }
}
