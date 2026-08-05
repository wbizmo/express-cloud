<?php

declare(strict_types=1);

namespace Tests\Feature\Accounting;

use App\Enums\Accounting\FinancialPostingClassification;
use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\FinancialPosting;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Services\Accounting\FinancialPostingCoordinator;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FinancialPostingBackboneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        AccountingPeriod::query()->create([
            'name' => 'Current year',
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
            'status' => 'open',
        ]);
    }

    #[Test]
    public function a_confirmed_sale_has_one_idempotent_balanced_source_journal(): void
    {
        $account = $this->account();
        $branch = $this->branch();
        $sale = Sale::query()->create([
            'sale_code' => 'INV-PHASE3-001',
            'sale_type' => SaleType::Invoice,
            'branch_id' => $branch->getKey(),
            'sold_by_account_id' => $account->getKey(),
            'sale_date' => today(),
            'subtotal_kobo' => 100_000,
            'discount_amount_kobo' => 0,
            'tax_amount_kobo' => 7_500,
            'grand_total_kobo' => 107_500,
            'paid_amount_kobo' => 0,
            'status' => SaleStatus::Confirmed,
            'idempotency_key' => 'phase3-sale-001',
            'confirmed_at' => now(),
        ]);

        $coordinator = app(FinancialPostingCoordinator::class);
        $first = $coordinator->sale($sale);
        $second = $coordinator->sale($sale);

        self::assertTrue($first->is($second));
        self::assertSame(FinancialPostingClassification::Posted, $first->classification);
        self::assertSame(1, FinancialPosting::query()->count());
        self::assertSame(1, JournalEntry::query()->count());

        $journal = $first->journalEntry()->with('lines')->firstOrFail();
        self::assertSame(
            (int) $journal->lines->sum('debit_kobo'),
            (int) $journal->lines->sum('credit_kobo'),
        );
        self::assertGreaterThan(0, (int) $journal->lines->sum('debit_kobo'));
    }

    #[Test]
    public function a_quote_is_explicitly_classified_as_non_posting(): void
    {
        $account = $this->account();
        $branch = $this->branch();
        $quote = Sale::query()->create([
            'sale_code' => 'QUO-PHASE3-001',
            'sale_type' => SaleType::Quote,
            'branch_id' => $branch->getKey(),
            'sold_by_account_id' => $account->getKey(),
            'sale_date' => today(),
            'subtotal_kobo' => 50_000,
            'discount_amount_kobo' => 0,
            'tax_amount_kobo' => 0,
            'grand_total_kobo' => 50_000,
            'paid_amount_kobo' => 0,
            'status' => SaleStatus::Draft,
            'idempotency_key' => 'phase3-quote-001',
        ]);

        $posting = app(FinancialPostingCoordinator::class)->sale($quote);

        self::assertSame(
            FinancialPostingClassification::NonPosting,
            $posting->classification,
        );
        self::assertNull($posting->journal_entry_id);
        self::assertSame('quote-is-non-financial', $posting->reason_code);
    }

    #[Test]
    public function core_source_paths_are_wired_to_immediate_posting(): void
    {
        $sale = file_get_contents(app_path('Actions/Sales/CreateSale.php'));
        $provider = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $observer = file_get_contents(app_path('Observers/FinancialSourceObserver.php'));
        $sync = file_get_contents(app_path('Console/Commands/SyncAccounting.php'));

        self::assertIsString($sale);
        self::assertIsString($provider);
        self::assertIsString($observer);
        self::assertIsString($sync);
        self::assertStringContainsString('FinancialPostingCoordinator', $sale);
        self::assertStringContainsString('$this->postings->sale($sale, $operation)', $sale);
        self::assertStringContainsString('FinancialSourceObserver::class', $provider);
        self::assertStringContainsString('supplierPayment', $observer);
        self::assertStringContainsString('stockMovement', $observer);
        self::assertStringContainsString('accounting:reconcile', $sync);
    }

    private function account(): Account
    {
        return Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Phase',
            'last_name' => 'Three',
            'login_key_encrypted' => 'test-ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => true,
        ]);
    }

    private function branch(): Branch
    {
        return Branch::query()->create([
            'name' => 'Phase Three Branch',
            'code' => 'PHASE-3',
            'address' => 'Test address',
            'status' => 'active',
            'is_head_office' => true,
        ]);
    }
}
