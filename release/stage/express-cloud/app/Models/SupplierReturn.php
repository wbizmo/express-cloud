<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupplierFinance\SupplierReturnStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupplierReturn extends Model
{
    use HasUlids;

    protected $table = 'supplier_returns';

    /** @var list<string> */
    protected $fillable = [
        'return_number',
        'supplier_id',
        'branch_id',
        'supplier_bill_id',
        'created_by_account_id',
        'status',
        'return_date',
        'total_kobo',
        'reason',
        'reference_note',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupplierReturnStatus::class,
            'return_date' => 'immutable_date',
            'total_kobo' => 'integer',
            'confirmed_at' => 'immutable_datetime',
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

    /** @return BelongsTo<SupplierBill, $this> */
    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    /** @return HasMany<SupplierReturnLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(SupplierReturnLine::class);
    }
}
