<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class BankAccount extends Model
{
    use HasUlids;

    protected $fillable = [
        'ledger_account_id', 'branch_id', 'name', 'bank_name',
        'account_number_masked', 'currency', 'status',
    ];
}
