<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Customer extends Model
{
    use HasUlids;

    protected $table = 'customers';

    /** @var list<string> */
    protected $fillable = [
        'deprecation_reason',
        'deprecated_by_account_id',
        'deprecated_at',
        'notes',
        'whatsapp_phone',
        'customer_code',
        'name',
        'phone',
        'email_encrypted',
        'address',
        'credit_limit_kobo',
        'balance_kobo',
        'is_wholesale',
        'status',
        'created_by_account_id',
        'customer_group_id',
        'customer_type',
        'contact_person',
        'billing_address',
        'shipping_address',
        'tax_number',
        'payment_terms_days',
        'price_group',
        'archived_at',
        'archived_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit_kobo' => 'integer',
            'balance_kobo' => 'integer',
            'is_wholesale' => 'boolean',
            'payment_terms_days' => 'integer',
            'archived_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'created_by_account_id',
        );
    }

    /** @return BelongsTo<CustomerGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(
            CustomerGroup::class,
            'customer_group_id',
        );
    }

    public function availableCreditKobo(): int
    {
        return max(
            0,
            $this->credit_limit_kobo - $this->balance_kobo,
        );
    }

    /**
     * A positive figure the business owes this customer, built up from
     * recorded overpayments (see AddSalePayment). This is separate from
     * receivables/debt tracking, which is computed live from unpaid
     * sales rather than from this field.
     */
    public function storeCreditKobo(): int
    {
        return max(0, -$this->balance_kobo);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
