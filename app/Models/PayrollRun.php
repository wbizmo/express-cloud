<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PayrollRun extends Model
{
    use HasUlids;

    protected $table = 'payroll_runs';

    /** @var list<string> */
    protected $fillable = [
        'run_number',
        'period_starts_on',
        'period_ends_on',
        'status',
        'prepared_by_account_id',
        'approved_by_account_id',
        'gross_total_kobo',
        'deduction_total_kobo',
        'net_total_kobo',
        'approved_at',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'period_starts_on' => 'immutable_date',
            'period_ends_on' => 'immutable_date',
            'gross_total_kobo' => 'integer',
            'deduction_total_kobo' => 'integer',
            'net_total_kobo' => 'integer',
            'approved_at' => 'immutable_datetime',
            'posted_at' => 'immutable_datetime',
        ];
    }
}
