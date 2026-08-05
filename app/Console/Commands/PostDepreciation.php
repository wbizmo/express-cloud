<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FixedAsset;
use App\Services\Accounting\FinancialPostingCoordinator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class PostDepreciation extends Command
{
    protected $signature = 'accounting:depreciation {period_end}';

    protected $description = 'Post straight-line depreciation for active fixed assets.';

    public function handle(FinancialPostingCoordinator $postings): int
    {
        $periodEnd = CarbonImmutable::parse(
            (string) $this->argument('period_end'),
        )->endOfMonth();
        $count = 0;

        FixedAsset::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($assets) use ($periodEnd, $postings, &$count): void {
                    foreach ($assets as $asset) {
                        $postings->depreciation($asset, $periodEnd);
                        $count++;
                    }
                },
            );

        $this->info("Depreciation posting completed for {$count} asset(s).");

        return self::SUCCESS;
    }
}
