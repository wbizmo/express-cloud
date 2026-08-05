<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupplierCreditNote extends Model
{
    use HasUlids;

    protected $fillable = [
        'credit_number',
        'supplier_id',
        'supplier_bill_id',
        'supplier_return_id',
        'branch_id',
        'created_by_account_id',
        'operation_request_id',
        'amount_kobo',
        'applied_kobo',
        'status',
        'reason',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'applied_kobo' => 'integer',
            'issued_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<SupplierCreditApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(SupplierCreditApplication::class);
    }

    public function remainingKobo(): int
    {
        return max(0, $this->amount_kobo - $this->applied_kobo);
    }
}
