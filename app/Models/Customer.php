<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Customer extends Model
{
    use HasUlids;

    protected $table = 'customers';

    /** @var list<string> */
    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'credit_limit_kobo' => 'integer',
            'balance_kobo' => 'integer',
            'is_wholesale' => 'boolean',
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

    public function availableCreditKobo(): int
    {
        return max(
            0,
            $this->credit_limit_kobo - $this->balance_kobo,
        );
    }
}
