<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Accounting\AccountType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LedgerAccount extends Model
{
    use HasUlids;

    protected $table = 'ledger_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'group_code',
        'normal_balance',
        'report_section',
        'cash_flow_section',
        'parent_id',
        'is_control_account',
        'requires_subledger',
        'tax_type',
        'is_system',
        'is_active',
        'allow_manual_posting',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_control_account' => 'boolean',
            'requires_subledger' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'allow_manual_posting' => 'boolean',
        ];
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<JournalLine, $this> */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'ledger_account_id');
    }
}
