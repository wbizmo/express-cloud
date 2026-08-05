<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Enums\Accounting\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\BankStatementLine;
use App\Models\Branch;
use App\Models\FinancialPosting;
use App\Models\InventoryValuationSnapshot;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStockBalance;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\FinancialPostingCoordinator;
use App\Services\Accounting\PeriodCloseService;
use App\Services\Inventory\InventoryValuationService;
use App\Services\Inventory\WarehouseStockLedger;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AccountingWarehouseRuntimeTest extends TestCase
{
    use RefreshDatabase;

    private Account $actor;

    private Branch $branch;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
        $this->actor = Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Enterprise',
            'last_name' => 'Operator',
            'login_key_encrypted' => 'test-ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => true,
        ]);
        $this->branch = Branch::query()->create([
            'name' => 'Enterprise Runtime Branch',
            'code' => 'ENT-RUNTIME',
            'address' => 'Test address',
            'status' => 'active',
            'is_head_office' => true,
        ]);
        $this->period = AccountingPeriod::query()->create([
            'name' => 'Enterprise runtime period',
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
            'status' => PeriodStatus::Open,
        ]);
    }

    #[Test]
    public function warehouse_movements_preserve_quantity_value_and_append_only_history(): void
    {
        $category = ProductCategory::query()->create([
            'name' => 'Runtime Inventory',
            'slug' => 'runtime-inventory',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'name' => 'Runtime Product',
            'sku' => 'RUNTIME-001',
            'category_id' => $category->getKey(),
            'track_inventory' => true,
            'tracks_batches' => false,
            'tracks_serials' => false,
            'default_price_kobo' => 1_000,
            'default_cost_price_kobo' => 500,
            'status' => 'active',
        ]);
        $source = $this->warehouse('WH-RUNTIME-A', true);
        $destination = $this->warehouse('WH-RUNTIME-B', false);
        $ledger = app(WarehouseStockLedger::class);

        $ledger->receive(
            $product,
            $source,
            $this->actor,
            10_000,
            500,
            referenceType: 'runtime_test',
            referenceId: 'receipt-1',
        );
        $ledger->transfer(
            $product,
            $source,
            $destination,
            $this->actor,
            3_000,
            note: 'Runtime transfer',
        );
        $reservation = $ledger->reserve(
            $product,
            $destination,
            $this->actor,
            2_000,
            'runtime_test',
            'reservation-1',
        );
        $ledger->releaseReservation($reservation, $this->actor);
        $ledger->countAdjustment(
            $product,
            $source,
            $this->actor,
            6_000,
            referenceId: 'count-1',
            note: 'Runtime count variance',
        );

        $balances = WarehouseStockBalance::query()
            ->where('product_id', $product->getKey())
            ->orderBy('warehouse_id')
            ->get();

        self::assertSame(2, $balances->count());
        self::assertSame(9_000, (int) $balances->sum('quantity_milliunits'));
        self::assertSame(0, (int) $balances->sum('reserved_milliunits'));
        self::assertSame(4_500, (int) $balances->sum('inventory_value_kobo'));
        self::assertSame(6, StockMovement::query()->count());
        self::assertSame(6, FinancialPosting::query()->count());
        self::assertSame(1, JournalEntry::query()->where('source_type', StockMovement::class)->count());

        self::assertSame(2, app(InventoryValuationService::class)->snapshot(today()));
        self::assertSame(2, app(InventoryValuationService::class)->snapshot(today()));
        self::assertSame(2, InventoryValuationSnapshot::query()->count());
    }

    #[Test]
    public function bank_reconciliation_and_period_close_enforce_zero_difference_controls(): void
    {
        $bankLedger = LedgerAccount::query()
            ->where('code', (string) config('accounting.codes.bank'))
            ->firstOrFail();
        $equityLedger = LedgerAccount::query()
            ->where('code', (string) config('accounting.codes.owner_equity'))
            ->firstOrFail();
        $bank = BankAccount::query()->create([
            'ledger_account_id' => $bankLedger->getKey(),
            'branch_id' => $this->branch->getKey(),
            'name' => 'Runtime Bank',
            'bank_name' => 'Runtime Bank Plc',
            'account_number_masked' => '******0012',
            'currency' => 'NGN',
            'status' => 'active',
        ]);
        $journal = JournalEntry::query()->create([
            'journal_number' => 'JRN-RUNTIME-001',
            'entry_date' => today(),
            'accounting_period_id' => $this->period->getKey(),
            'branch_id' => $this->branch->getKey(),
            'source_type' => 'runtime_test',
            'source_id' => 'bank-opening',
            'source_event' => 'opening',
            'status' => 'posted',
            'book_type' => 'bank',
            'memo' => 'Runtime bank opening',
            'created_by_account_id' => $this->actor->getKey(),
            'posted_at' => now(),
        ]);
        $bankLine = JournalLine::query()->create([
            'journal_entry_id' => $journal->getKey(),
            'ledger_account_id' => $bankLedger->getKey(),
            'branch_id' => $this->branch->getKey(),
            'debit_kobo' => 10_000,
            'credit_kobo' => 0,
            'description' => 'Runtime bank debit',
        ]);
        JournalLine::query()->create([
            'journal_entry_id' => $journal->getKey(),
            'ledger_account_id' => $equityLedger->getKey(),
            'branch_id' => $this->branch->getKey(),
            'debit_kobo' => 0,
            'credit_kobo' => 10_000,
            'description' => 'Runtime opening equity',
        ]);
        app(FinancialPostingCoordinator::class)
            ->registerExistingJournal($journal, 'runtime-opening');

        $reconciliation = app(BankReconciliationService::class);
        $statement = $reconciliation->import(
            $bank,
            $this->actor,
            'runtime-bank-import',
            today()->toDateString(),
            today()->toDateString(),
            0,
            10_000,
            [[
                'transaction_date' => today()->toDateString(),
                'reference' => 'BANK-001',
                'description' => 'Opening bank deposit',
                'credit_kobo' => 10_000,
                'running_balance_kobo' => 10_000,
            ]],
        );
        $statementLine = BankStatementLine::query()
            ->where('bank_statement_import_id', $statement->getKey())
            ->firstOrFail();
        $reconciliation->match($statementLine, $bankLine, $this->actor);
        $finalized = $reconciliation->finalize($statement);

        self::assertSame('reconciled', $finalized->status);
        self::assertNotNull($finalized->reconciled_at);

        $batch = app(PeriodCloseService::class)->close(
            $this->period,
            $this->actor,
            'Runtime zero-difference close',
        );

        self::assertSame('locked', $batch->status);
        self::assertSame(PeriodStatus::Locked, $this->period->refresh()->status);
        self::assertNotNull($journal->refresh()->locked_at);
    }

    private function warehouse(string $code, bool $default): Warehouse
    {
        return Warehouse::query()->create([
            'branch_id' => $this->branch->getKey(),
            'code' => $code,
            'name' => $code,
            'type' => 'standard',
            'status' => 'active',
            'is_default' => $default,
            'allows_sales' => true,
            'allows_receipts' => true,
        ]);
    }
}
