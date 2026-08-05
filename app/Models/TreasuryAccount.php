<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class TreasuryAccount extends Model
{
    use HasUlids;

    protected $fillable = [
        'ledger_account_id', 'branch_id', 'name', 'type', 'currency', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
