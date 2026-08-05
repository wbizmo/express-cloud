<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AccrualSchedule extends Model
{
    use HasUlids;

    protected $fillable = [
        'schedule_number',
        'branch_id',
        'expense_ledger_account_id',
        'balance_sheet_ledger_account_id',
        'created_by_account_id',
        'operation_request_id',
        'schedule_type',
        'total_kobo',
        'period_count',
        'starts_on',
        'ends_on',
        'posted_periods',
        'status',
        'memo',
    ];

    protected function casts(): array
    {
        return [
            'total_kobo' => 'integer',
            'period_count' => 'integer',
            'posted_periods' => 'integer',
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
        ];
    }

    /** @return HasMany<AccrualPosting, $this> */
    public function postings(): HasMany
    {
        return $this->hasMany(AccrualPosting::class);
    }

    public function periodicAmountKobo(int $periodNumber): int
    {
        if ($this->period_count <= 0 || $periodNumber < 1 || $periodNumber > $this->period_count) {
            throw new \InvalidArgumentException('The accrual period number is invalid.');
        }

        $base = intdiv($this->total_kobo, $this->period_count);
        $remainder = $this->total_kobo % $this->period_count;

        return $base + ($periodNumber <= $remainder ? 1 : 0);
    }
}
