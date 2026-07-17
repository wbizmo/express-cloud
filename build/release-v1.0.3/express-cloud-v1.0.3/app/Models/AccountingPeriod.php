<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Accounting\PeriodStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AccountingPeriod extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'status',
        'closed_by_account_id',
        'closed_at',
        'locked_by_account_id',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'immutable_date',
            'ends_on' => 'immutable_date',
            'status' => PeriodStatus::class,
            'closed_at' => 'immutable_datetime',
            'locked_at' => 'immutable_datetime',
        ];
    }

    public function acceptsPostings(): bool
    {
        return $this->status === PeriodStatus::Open;
    }
}
