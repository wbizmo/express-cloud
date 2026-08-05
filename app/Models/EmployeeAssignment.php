<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class EmployeeAssignment extends Model
{
    use HasUlids;

    protected $table = 'employee_assignments';

    /** @var list<string> */
    protected $fillable = [
        'employee_id',
        'branch_id',
        'department_id',
        'job_role_id',
        'starts_on',
        'ends_on',
        'assigned_by_account_id',
        'memo',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
        ];
    }
}
