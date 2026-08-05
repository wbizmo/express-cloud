<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AttendanceRecord extends Model
{
    use HasUlids;

    protected $table = 'attendance_records';

    /** @var list<string> */
    protected $fillable = [
        'employee_id',
        'branch_id',
        'work_date',
        'clocked_in_at',
        'clocked_out_at',
        'worked_minutes',
        'status',
        'recorded_by_account_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'immutable_date',
            'clocked_in_at' => 'immutable_datetime',
            'clocked_out_at' => 'immutable_datetime',
            'worked_minutes' => 'integer',
        ];
    }
}
