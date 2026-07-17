<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Accounting\AccountType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class LedgerAccount extends Model
{
    use HasUlids;

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'is_control_account',
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
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'allow_manual_posting' => 'boolean',
        ];
    }
}
