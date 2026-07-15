<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Sale extends Model
{
    use HasUlids;

    protected $table = 'sales';

    /** @var list<string> */
    protected $fillable = [
        'sale_code',
        'sale_type',
        'branch_id',
        'customer_id',
        'sold_by_account_id',
        'converted_from_sale_id',
        'sale_date',
        'subtotal_kobo',
        'discount_amount_kobo',
        'tax_amount_kobo',
        'grand_total_kobo',
        'paid_amount_kobo',
        'status',
        'idempotency_key',
        'notes',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'sale_type' => SaleType::class,
            'status' => SaleStatus::class,
            'sale_date' => 'immutable_date',
            'subtotal_kobo' => 'integer',
            'discount_amount_kobo' => 'integer',
            'tax_amount_kobo' => 'integer',
            'grand_total_kobo' => 'integer',
            'paid_amount_kobo' => 'integer',
            'confirmed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'sold_by_account_id',
        );
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balanceDueKobo(): int
    {
        return max(
            0,
            $this->grand_total_kobo - $this->paid_amount_kobo,
        );
    }
}
