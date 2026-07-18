<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class DailyReportRun extends Model
{
    use HasUlids;

    protected $fillable = ['report_date', 'status', 'generated_files', 'summary_html', 'failure_message', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['report_date' => 'date', 'generated_files' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
