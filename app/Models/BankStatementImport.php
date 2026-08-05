<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BankStatementImport extends Model
{
    use HasUlids;

    protected $fillable = [
        'bank_account_id',
        'imported_by_account_id',
        'operation_request_id',
        'starts_on',
        'ends_on',
        'opening_balance_kobo',
        'closing_balance_kobo',
        'file_hash',
        'status',
        'imported_at',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'opening_balance_kobo' => 'integer',
            'closing_balance_kobo' => 'integer',
            'imported_at' => 'immutable_datetime',
            'reconciled_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<BankAccount, $this> */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /** @return HasMany<BankStatementLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }
}
