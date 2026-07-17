<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Commercial\PurchaseReceiptStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseReceipt extends Model
{
    use HasUlids;

    protected $fillable = [
        'receipt_number',
        'supplier_id',
        'branch_id',
        'recorded_by_account_id',
        'purchase_order_id',
        'purchased_at',
        'supplier_reference',
        'subtotal_kobo',
        'discount_kobo',
        'tax_kobo',
        'total_kobo',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'immutable_date',
            'subtotal_kobo' => 'integer',
            'discount_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'total_kobo' => 'integer',
            'status' => PurchaseReceiptStatus::class,
        ];
    }

    /** @return HasMany<PurchaseReceiptLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReceiptLine::class);
    }
}
