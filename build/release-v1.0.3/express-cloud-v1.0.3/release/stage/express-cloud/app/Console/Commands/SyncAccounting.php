<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FixedAsset;
use App\Models\Payment;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StandaloneReceipt;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use App\Services\Accounting\OperationalAccountingProjector;
use Illuminate\Console\Command;

final class SyncAccounting extends Command
{
    protected $signature = 'accounting:sync';

    protected $description = 'Project operational records into the journal idempotently.';

    public function handle(
        OperationalAccountingProjector $projector,
    ): int {
        Sale::query()->with('items')->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->sale($row);
                }
            },
        );

        Payment::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->payment($row);
                }
            },
        );

        PurchaseReceipt::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->purchase($row);
                }
            },
        );

        SupplierBill::query()
            ->whereNotNull('posted_at')
            ->orderBy('id')
            ->chunkById(
                100,
                static function ($rows) use ($projector): void {
                    foreach ($rows as $row) {
                        $projector->supplierBill($row);
                    }
                },
            );

        SupplierBillPayment::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->supplierPayment($row);
                }
            },
        );

        SaleReturn::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->saleReturn($row);
                }
            },
        );

        PurchaseReturn::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->purchaseReturn($row);
                }
            },
        );

        StandaloneReceipt::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->standaloneReceipt($row);
                }
            },
        );

        FixedAsset::query()->orderBy('id')->chunkById(
            100,
            static function ($rows) use ($projector): void {
                foreach ($rows as $row) {
                    $projector->fixedAsset($row);
                }
            },
        );

        $this->info('Accounting synchronization completed.');

        return self::SUCCESS;
    }
}
