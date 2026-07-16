<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\AccountingPeriod;
use Carbon\CarbonInterface;

final class PeriodResolver
{
    public function forDate(CarbonInterface $date): AccountingPeriod
    {
        /** @var AccountingPeriod|null $period */
        $period = AccountingPeriod::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();

        if ($period === null) {
            throw new \DomainException(
                'No accounting period covers the journal date.',
            );
        }

        if (! $period->acceptsPostings()) {
            throw new \DomainException(
                'The accounting period is closed or locked.',
            );
        }

        return $period;
    }
}
