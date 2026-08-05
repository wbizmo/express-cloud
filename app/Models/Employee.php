<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Employee extends Model
{
    use HasUlids;

    protected $table = 'employees';

    /** @var list<string> */
    protected $fillable = [
        'employee_code',
        'account_id',
        'branch_id',
        'department_id',
        'job_role_id',
        'first_name',
        'last_name',
        'email_encrypted',
        'phone',
        'hired_on',
        'terminated_on',
        'employment_type',
        'status',
        'created_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'hired_on' => 'immutable_date',
            'terminated_on' => 'immutable_date',
        ];
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
