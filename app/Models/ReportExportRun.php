<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ReportExportRun extends Model
{
    use HasUlids;

    protected $table = 'report_export_runs';

    /** @var list<string> */
    protected $fillable = [
        'report_key',
        'requested_by_account_id',
        'filters',
        'format',
        'status',
        'row_count',
        'storage_path',
        'failure_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'row_count' => 'integer',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
