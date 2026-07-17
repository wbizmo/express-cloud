<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AssetDepreciationPosting;
use App\Models\FixedAsset;
use App\Services\Accounting\AccountLocator;
use App\Services\Accounting\JournalPoster;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class PostDepreciation extends Command
{
    protected $signature = 'accounting:depreciation {period_end}';

    protected $description = 'Post straight-line depreciation for active fixed assets.';

    public function handle(
        AccountLocator $accounts,
        JournalPoster $journals,
    ): int {
        $periodEnd = CarbonImmutable::parse(
            (string) $this->argument('period_end'),
        )->endOfMonth();

        FixedAsset::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($assets) use (
                    $periodEnd,
                    $accounts,
                    $journals,
                ): void {
                    foreach ($assets as $asset) {
                        if (
                            AssetDepreciationPosting::query()
                                ->where('fixed_asset_id', $asset->getKey())
                                ->whereDate(
                                    'period_end',
                                    $periodEnd->toDateString(),
                                )
                                ->exists()
                        ) {
                            continue;
                        }

                        $amount = $asset->monthlyDepreciationKobo();

                        if ($amount <= 0) {
                            continue;
                        }

                        $entry = $journals->post(
                            $periodEnd,
                            "Depreciation {$asset->asset_code}",
                            [
                                [
                                    'account_id' => $accounts
                                        ->configured('depreciation_expense')
                                        ->getKey(),
                                    'debit_kobo' => $amount,
                                ],
                                [
                                    'account_id' => $accounts
                                        ->configured('accumulated_depreciation')
                                        ->getKey(),
                                    'credit_kobo' => $amount,
                                ],
                            ],
                            $asset->branch_id,
                            $asset->created_by_account_id,
                            FixedAsset::class,
                            (string) $asset->getKey(),
                            'depreciation-'.$periodEnd->format('Y-m'),
                        );

                        AssetDepreciationPosting::query()->create([
                            'fixed_asset_id' => $asset->getKey(),
                            'journal_entry_id' => $entry->getKey(),
                            'period_end' => $periodEnd->toDateString(),
                            'amount_kobo' => $amount,
                        ]);
                    }
                },
            );

        $this->info('Depreciation posting completed.');

        return self::SUCCESS;
    }
}
