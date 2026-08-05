<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Holiday extends Model
{
    use HasUlids;

    protected $table = 'holidays';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'holiday_date',
        'branch_id',
        'is_paid',
        'is_active',
        'created_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'immutable_date',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
