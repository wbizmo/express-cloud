<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Inventory\InventoryValuationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class SnapshotInventoryValuation extends Command
{
    protected $signature = 'inventory:valuation-snapshot
        {--date= : Snapshot date in YYYY-MM-DD format}
        {--fail-on-gap : Fail when negative balances or reservation overruns exist}';

    protected $description = 'Capture warehouse inventory valuation and verify stock balance invariants.';

    public function handle(InventoryValuationService $valuation): int
    {
        $date = CarbonImmutable::parse((string) ($this->option('date') ?: today()->toDateString()));
        $captured = $valuation->snapshot($date);
        $audit = $valuation->audit();

        $this->table(
            ['Snapshot date', 'Rows captured', 'Balance rows', 'Negative rows', 'Reservation overruns', 'Total value (kobo)'],
            [[
                $date->toDateString(),
                $captured,
                $audit['balance_rows'],
                $audit['negative_rows'],
                $audit['reserved_overruns'],
                $audit['total_value_kobo'],
            ]],
        );

        $invalid = $audit['negative_rows'] + $audit['reserved_overruns'];
        if ($invalid === 0) {
            $this->info('Warehouse valuation snapshot completed with valid stock invariants.');

            return self::SUCCESS;
        }

        $this->error("Warehouse valuation audit found {$invalid} invalid balance rows.");

        return $this->option('fail-on-gap') ? self::FAILURE : self::SUCCESS;
    }
}
