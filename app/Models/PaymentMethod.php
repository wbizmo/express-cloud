<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentMethod extends Model
{
    use HasUlids;

    protected $table = 'payment_methods';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'account_number_encrypted',
        'bank_name',
        'description',
        'is_system_default',
        'is_default_for_pos',
        'is_active',
        'ledger_account_id',
        'created_by_account_id',
        'method_type',
        'is_visible_in_pos',
        'is_visible_in_commerce',
        'requires_reference',
        'requires_approval',
        'settlement_ledger_account_id',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'is_system_default' => 'boolean',
            'is_default_for_pos' => 'boolean',
            'is_active' => 'boolean',
            'is_visible_in_pos' => 'boolean',
            'is_visible_in_commerce' => 'boolean',
            'requires_reference' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    /** @return BelongsTo<LedgerAccount, $this> */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function mayBeDeleted(): bool
    {
        return ! $this->is_system_default;
    }
}
