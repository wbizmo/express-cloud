<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountingOperations\PurchaseReturnStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseReturn extends Model
{
    use HasUlids;

    protected $fillable = [
        'return_number',
        'purchase_receipt_id',
        'supplier_id',
        'branch_id',
        'processed_by_account_id',
        'total_kobo',
        'supplier_credit_reference',
        'status',
        'reason',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'total_kobo' => 'integer',
            'status' => PurchaseReturnStatus::class,
            'returned_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<PurchaseReturnLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class);
    }
}
