<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Department extends Model
{
    use HasUlids;

    protected $table = 'departments';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'branch_id',
        'manager_account_id',
        'status',
    ];
}
