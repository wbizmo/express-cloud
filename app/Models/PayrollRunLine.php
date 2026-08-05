<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PayrollRunLine extends Model
{
    use HasUlids;

    protected $table = 'payroll_run_lines';

    /** @var list<string> */
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'gross_kobo',
        'deductions_kobo',
        'net_kobo',
        'components',
    ];

    protected function casts(): array
    {
        return [
            'gross_kobo' => 'integer',
            'deductions_kobo' => 'integer',
            'net_kobo' => 'integer',
            'components' => 'array',
        ];
    }
}
