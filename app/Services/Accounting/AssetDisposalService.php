<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Enums\Accounting\FinancialPostingClassification;
use App\Enums\AccountingOperations\FixedAssetStatus;
use App\Models\Account;
use App\Models\AssetDisposal;
use App\Models\FinancialPosting;
use App\Models\FixedAsset;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class AssetDisposalService
{
    public function __construct(
        private JournalPoster $journals,
        private AccountLocator $accounts,
    ) {}

    public function dispose(
        FixedAsset $asset,
        Account $actor,
        string $disposedOn,
        int $proceedsKobo,
        string $method,
        ?string $reference = null,
        ?string $notes = null,
    ): AssetDisposal {
        if ($proceedsKobo < 0) {
            throw new \InvalidArgumentException('Asset disposal proceeds cannot be negative.');
        }

        return DB::transaction(function () use (
            $asset, $actor, $disposedOn, $proceedsKobo, $method, $reference, $notes,
        ): AssetDisposal {
            /** @var FixedAsset $locked */
            $locked = FixedAsset::query()->whereKey($asset->getKey())
                ->lockForUpdate()->firstOrFail();
            if ($locked->status !== FixedAssetStatus::Active) {
                throw new \DomainException('Only an active fixed asset can be disposed.');
            }

            $date = CarbonImmutable::parse($disposedOn);
            $months = max(
                0,
                min(
                    $locked->useful_life_months,
                    CarbonImmutable::parse($locked->acquired_at)
                        ->startOfMonth()
                        ->diffInMonths($date->startOfMonth()),
                ),
            );
            $accumulated = min(
                max(0, $locked->cost_kobo - $locked->salvage_value_kobo),
                $locked->monthlyDepreciationKobo() * $months,
            );
            $netBookValue = max(0, $locked->cost_kobo - $accumulated);
            $gainLoss = $proceedsKobo - $netBookValue;

            $lines = [];
            if ($proceedsKobo > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts->configured('bank')->getKey(),
                    'debit_kobo' => $proceedsKobo,
                ];
            }
            if ($accumulated > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('accumulated_depreciation')->getKey(),
                    'debit_kobo' => $accumulated,
                ];
            }
            if ($gainLoss < 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('asset_disposal_loss')->getKey(),
                    'debit_kobo' => abs($gainLoss),
                ];
            }
            $lines[] = [
                'account_id' => (string) $this->accounts->configured('fixed_assets')->getKey(),
                'credit_kobo' => $locked->cost_kobo,
            ];
            if ($gainLoss > 0) {
                $lines[] = [
                    'account_id' => (string) $this->accounts
                        ->configured('asset_disposal_gain')->getKey(),
                    'credit_kobo' => $gainLoss,
                ];
            }

            $journal = $this->journals->post(
                $date,
                'Asset disposal: '.$locked->asset_code.' '.$locked->name,
                $lines,
                $locked->branch_id,
                (string) $actor->getKey(),
                FixedAsset::class,
                (string) $locked->getKey(),
                'disposed',
                null,
                null,
                'asset',
            );
            $disposal = AssetDisposal::query()->create([
                'fixed_asset_id' => $locked->getKey(),
                'journal_entry_id' => $journal->getKey(),
                'disposed_by_account_id' => $actor->getKey(),
                'disposed_on' => $date->toDateString(),
                'proceeds_kobo' => $proceedsKobo,
                'net_book_value_kobo' => $netBookValue,
                'gain_loss_kobo' => $gainLoss,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
            ]);
            $locked->forceFill(['status' => FixedAssetStatus::Disposed])->save();

            FinancialPosting::query()->firstOrCreate(
                [
                    'source_type' => AssetDisposal::class,
                    'source_id' => (string) $disposal->getKey(),
                    'source_event' => 'disposed',
                ],
                [
                    'classification' => FinancialPostingClassification::Posted,
                    'journal_entry_id' => $journal->getKey(),
                    'reason_code' => 'asset-disposal',
                    'details' => [
                        'fixed_asset_id' => (string) $locked->getKey(),
                        'net_book_value_kobo' => $netBookValue,
                        'proceeds_kobo' => $proceedsKobo,
                        'gain_loss_kobo' => $gainLoss,
                    ],
                    'classified_at' => now(),
                ],
            );

            return $disposal;
        }, 3);
    }
}
