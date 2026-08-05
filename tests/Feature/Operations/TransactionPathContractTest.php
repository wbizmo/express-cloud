<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TransactionPathContractTest extends TestCase
{
    #[Test]
    public function retryable_write_requests_require_idempotency_keys(): void
    {
        foreach ([
            app_path('Http/Requests/Sales/StoreSaleRequest.php'),
            app_path('Http/Requests/Sales/AddSalePaymentRequest.php'),
            app_path('Http/Requests/Commercial/StoreSaleReturnRequest.php'),
            app_path('Http/Requests/Admin/Inventory/StockIntakeRequest.php'),
            app_path('Http/Requests/Admin/Inventory/StockTransferRequest.php'),
            app_path('Http/Requests/Admin/Inventory/StockAdjustmentRequest.php'),
        ] as $path) {
            $contents = file_get_contents($path);
            self::assertIsString($contents);
            self::assertStringContainsString("'idempotency_key'", $contents);
        }
    }

    #[Test]
    public function core_write_paths_use_the_command_boundary_and_persist_operation_links(): void
    {
        foreach ([
            app_path('Actions/Sales/CreateSale.php'),
            app_path('Actions/Sales/AddSalePayment.php'),
            app_path('Actions/Commercial/CreateSaleReturn.php'),
            app_path('Http/Controllers/Admin/Inventory/InventoryController.php'),
            app_path('Http/Controllers/Admin/Accounting/BatchJournalEntryController.php'),
        ] as $path) {
            $contents = file_get_contents($path);
            self::assertIsString($contents);
            self::assertStringContainsString('CommandBoundary', $contents);
        }

        foreach ([
            app_path('Actions/Sales/CreateSale.php'),
            app_path('Actions/Sales/AddSalePayment.php'),
            app_path('Actions/Commercial/CreateSaleReturn.php'),
            app_path('Http/Controllers/Admin/Accounting/BatchJournalEntryController.php'),
        ] as $path) {
            $contents = file_get_contents($path);
            self::assertIsString($contents);
            self::assertStringContainsString('operation_request_id', $contents);
        }

        $inventory = file_get_contents(
            app_path('Http/Controllers/Admin/Inventory/InventoryController.php'),
        );
        $ledger = file_get_contents(
            app_path('Services/Inventory/StockLedger.php'),
        );

        self::assertIsString($inventory);
        self::assertIsString($ledger);
        self::assertStringContainsString('OperationRequest $operation', $inventory);
        self::assertStringContainsString('$this->ledger->intake(', $inventory);
        self::assertStringContainsString('$this->ledger->transfer(', $inventory);
        self::assertStringContainsString('$this->ledger->adjust(', $inventory);
        self::assertStringContainsString(
            '\'operation_request_id\' => $operation?->getKey()',
            $ledger,
        );
    }

    #[Test]
    public function stock_transfer_locks_balances_in_stable_branch_order(): void
    {
        $contents = file_get_contents(
            app_path('Services/Inventory/StockLedger.php'),
        );

        self::assertIsString($contents);
        self::assertStringContainsString('->sort()', $contents);
        self::assertStringContainsString("->orderBy('branch_id')", $contents);
        self::assertStringContainsString('lockForUpdate()', $contents);
    }

    #[Test]
    public function payment_paths_reject_overruns_instead_of_clamping_totals(): void
    {
        $create = file_get_contents(app_path('Actions/Sales/CreateSale.php'));
        $payment = file_get_contents(
            app_path('Actions/Sales/AddSalePayment.php'),
        );

        self::assertIsString($create);
        self::assertIsString($payment);
        self::assertStringContainsString('Overpayments are not permitted', $create);
        self::assertStringContainsString('Overpayments are not permitted', $payment);
        self::assertStringNotContainsString('min($paidTotal, $grandTotal)', $create);
    }
}
