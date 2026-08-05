<?php

declare(strict_types=1);

namespace Tests\Feature\Phase14;

use App\Enums\Accounting\PeriodStatus;
use App\Enums\Commercial\PurchaseReceiptStatus;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\FinancialPosting;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseStockBalance;
use App\Services\Accounting\FinancialPostingCoordinator;
use App\Services\Inventory\StockLedger;
use App\Services\Inventory\WarehouseStockLedger;
use App\Services\Procurement\ProcurementReversalService;
use App\Services\Procurement\PurchaseOrderLifecycleService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProcurementLifecycleRuntimeTest extends TestCase
{
    use RefreshDatabase;

    private Account $actor;

    private Branch $source;

    private Branch $destination;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
        AccountingPeriod::query()->create([
            'name' => 'Phase 14 period',
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
            'status' => PeriodStatus::Open,
        ]);

        $this->actor = Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Phase',
            'last_name' => 'Fourteen',
            'login_key_encrypted' => 'test-ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => true,
        ]);
        $this->source = $this->branch('P14-SOURCE', 'Phase 14 Source');
        $this->destination = $this->branch('P14-DEST', 'Phase 14 Destination');
        $this->supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-P14',
            'company_name' => 'Phase Fourteen Supplies',
            'contact_person' => 'Procurement Lead',
            'category' => 'General',
            'phone' => '+2348000000000',
            'payment_terms_days' => 30,
            'credit_limit_kobo' => 0,
            'lead_time_days' => 3,
            'is_preferred' => true,
            'status' => 'active',
        ]);
        $category = ProductCategory::query()->create([
            'name' => 'Phase 14 Products',
            'slug' => 'phase-14-products',
            'status' => 'active',
        ]);
        $this->product = Product::query()->create([
            'name' => 'Phase 14 Runtime Product',
            'sku' => 'P14-001',
            'category_id' => $category->getKey(),
            'track_inventory' => true,
            'default_price_kobo' => 2_000,
            'default_cost_price_kobo' => 1_000,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function approved_unreceived_order_can_be_revised_and_returns_to_draft(): void
    {
        $order = $this->order(PurchaseOrderStatus::Approved, 5_000, 0);
        $updated = app(PurchaseOrderLifecycleService::class)->revise(
            $order,
            $this->actor,
            [
                'supplier_id' => (string) $this->supplier->getKey(),
                'branch_id' => (string) $this->destination->getKey(),
                'expected_at' => now()->addDays(7)->toDateString(),
                'reference_note' => 'Revised quantities and destination.',
                'lines' => [[
                    'product_id' => (string) $this->product->getKey(),
                    'quantity' => '8.000',
                    'unit_cost' => '12.50',
                    'tax_rate_percent' => '7.50',
                ]],
            ],
        );

        self::assertSame(PurchaseOrderStatus::Draft, $updated->status);
        self::assertNull($updated->approved_at);
        self::assertSame((string) $this->destination->getKey(), (string) $updated->branch_id);
        self::assertSame(1, $updated->lines()->count());
        self::assertSame(8_000, $updated->lines()->firstOrFail()->ordered_quantity_milliunits);
        self::assertSame(10_000, $updated->subtotal_kobo);
        self::assertSame(750, $updated->tax_kobo);
        self::assertSame(10_750, $updated->total_kobo);
    }

    #[Test]
    public function cancellation_preserves_order_and_line_history(): void
    {
        $order = $this->order(PurchaseOrderStatus::Approved, 5_000, 0);
        $cancelled = app(PurchaseOrderLifecycleService::class)
            ->cancel($order, $this->actor, 'Supplier could not fulfil the order.');

        self::assertSame(PurchaseOrderStatus::Cancelled, $cancelled->status);
        self::assertNotNull($cancelled->closed_at);
        self::assertSame(1, PurchaseOrder::query()->whereKey($order->getKey())->count());
        $line = $cancelled->lines()->firstOrFail();
        self::assertSame($line->ordered_quantity_milliunits, $line->cancelled_quantity_milliunits);
        self::assertSame(0, $line->remainingMilliunits());
    }

    #[Test]
    public function partially_received_order_cancels_only_the_outstanding_quantity(): void
    {
        $order = $this->order(PurchaseOrderStatus::PartiallyReceived, 10_000, 4_000);
        $closed = app(PurchaseOrderLifecycleService::class)
            ->cancelOutstanding($order, $this->actor, 'Close remaining supplier backorder.');

        self::assertSame(PurchaseOrderStatus::PartiallyCancelled, $closed->status);
        $line = $closed->lines()->firstOrFail();
        self::assertSame(4_000, $line->received_quantity_milliunits);
        self::assertSame(6_000, $line->cancelled_quantity_milliunits);
        self::assertSame(0, $line->remainingMilliunits());
    }

    #[Test]
    public function branch_stock_transfer_posts_one_paired_atomic_movement(): void
    {
        $ledger = app(StockLedger::class);
        $ledger->intake(
            $this->product,
            $this->source,
            $this->actor,
            12_000,
            1_000,
            'phase14_test',
            'intake-1',
            'Phase 14 transfer setup',
        );
        $pair = $ledger->transfer(
            $this->product,
            $this->source,
            $this->destination,
            $this->actor,
            5_000,
            'Phase 14 transfer verification',
        );

        self::assertSame(-5_000, $pair['out']->quantity_delta_milliunits);
        self::assertSame(5_000, $pair['in']->quantity_delta_milliunits);
        self::assertSame($pair['out']->correlation_id, $pair['in']->correlation_id);
        self::assertSame(7_000, ProductBranchStock::query()
            ->where('product_id', $this->product->getKey())
            ->where('branch_id', $this->source->getKey())
            ->value('quantity_milliunits'));
        self::assertSame(5_000, ProductBranchStock::query()
            ->where('product_id', $this->product->getKey())
            ->where('branch_id', $this->destination->getKey())
            ->value('quantity_milliunits'));
        self::assertSame(2, StockMovement::query()
            ->where('correlation_id', $pair['out']->correlation_id)->count());
    }

    #[Test]
    public function warehouse_receipt_and_landed_cost_can_be_reversed_without_deleting_history(): void
    {
        $warehouse = Warehouse::query()->create([
            'branch_id' => $this->source->getKey(),
            'code' => 'P14-WH',
            'name' => 'Phase 14 Warehouse',
            'type' => 'warehouse',
            'status' => 'active',
            'is_default' => true,
            'allows_sales' => true,
            'allows_receipts' => true,
        ]);
        $ledger = app(WarehouseStockLedger::class);
        $ledger->receive(
            $this->product,
            $warehouse,
            $this->actor,
            10_000,
            1_000,
            referenceType: 'phase14_test',
            referenceId: 'receipt-1',
        );
        $ledger->capitalizeCost(
            $this->product,
            $warehouse,
            $this->actor,
            2_000,
            referenceId: 'landed-cost-1',
        );
        $ledger->reverseCapitalizedCost(
            $this->product,
            $warehouse,
            $this->actor,
            2_000,
            referenceId: 'landed-cost-1',
            note: 'Phase 14 reversal test',
        );
        $ledger->reverseReceipt(
            $this->product,
            $warehouse,
            $this->actor,
            4_000,
            referenceType: 'goods_receipt_void',
            referenceId: 'receipt-1',
            note: 'Phase 14 void test',
        );

        $balance = WarehouseStockBalance::query()
            ->where('warehouse_id', $warehouse->getKey())
            ->where('product_id', $this->product->getKey())
            ->firstOrFail();
        self::assertSame(6_000, $balance->quantity_milliunits);
        self::assertSame(6_000, $balance->inventory_value_kobo);
        self::assertSame(1, StockMovement::query()
            ->where('movement_type', StockMovementType::PurchaseReturn)->count());
        self::assertSame(1, StockMovement::query()
            ->where('movement_type', StockMovementType::CostReversal)->count());
    }

    #[Test]
    public function goods_receipt_void_service_posts_compensating_stock_and_journal_history(): void
    {
        $warehouse = Warehouse::query()->create([
            'branch_id' => $this->source->getKey(),
            'code' => 'P14-VOID-WH',
            'name' => 'Phase 14 Void Warehouse',
            'type' => 'warehouse',
            'status' => 'active',
            'is_default' => true,
            'allows_sales' => true,
            'allows_receipts' => true,
        ]);
        $order = $this->order(PurchaseOrderStatus::Received, 10_000, 10_000);
        $order->forceFill([
            'warehouse_id' => $warehouse->getKey(),
            'received_at' => now(),
            'closed_at' => now(),
        ])->save();
        $purchaseReceipt = PurchaseReceipt::query()->create([
            'receipt_number' => 'PUR-P14-VOID',
            'supplier_id' => $this->supplier->getKey(),
            'branch_id' => $this->source->getKey(),
            'recorded_by_account_id' => $this->actor->getKey(),
            'purchase_order_id' => $order->getKey(),
            'purchased_at' => today(),
            'subtotal_kobo' => 10_000,
            'discount_kobo' => 0,
            'tax_kobo' => 0,
            'total_kobo' => 10_000,
            'status' => PurchaseReceiptStatus::Recorded,
            'notes' => 'Phase 14 receipt void test.',
        ]);
        app(FinancialPostingCoordinator::class)->purchaseReceipt($purchaseReceipt);
        $receipt = GoodsReceipt::query()->create([
            'receipt_number' => 'GRN-P14-VOID',
            'purchase_order_id' => $order->getKey(),
            'purchase_receipt_id' => $purchaseReceipt->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'received_by_account_id' => $this->actor->getKey(),
            'status' => 'received',
            'subtotal_kobo' => 10_000,
            'tax_kobo' => 0,
            'total_kobo' => 10_000,
            'received_at' => now(),
        ]);
        $orderLine = $order->lines()->firstOrFail();
        GoodsReceiptLine::query()->create([
            'goods_receipt_id' => $receipt->getKey(),
            'purchase_order_line_id' => $orderLine->getKey(),
            'product_id' => $this->product->getKey(),
            'received_quantity_milliunits' => 10_000,
            'accepted_quantity_milliunits' => 10_000,
            'quarantined_quantity_milliunits' => 0,
            'unit_cost_kobo' => 1_000,
            'tax_kobo' => 0,
            'line_total_kobo' => 10_000,
        ]);
        app(WarehouseStockLedger::class)->receive(
            $this->product,
            $warehouse,
            $this->actor,
            10_000,
            1_000,
            referenceType: 'goods_receipt',
            referenceId: (string) $receipt->getKey(),
        );

        $voided = app(ProcurementReversalService::class)->voidReceipt(
            $receipt,
            $this->actor,
            'Supplier shipment rejected after inspection.',
        );

        self::assertSame('voided', $voided->status);
        self::assertSame(0, WarehouseStockBalance::query()
            ->where('warehouse_id', $warehouse->getKey())
            ->where('product_id', $this->product->getKey())
            ->value('quantity_milliunits'));
        self::assertSame(PurchaseOrderStatus::Approved, $order->refresh()->status);
        self::assertSame(0, $orderLine->refresh()->received_quantity_milliunits);
        self::assertSame(PurchaseReceiptStatus::Voided, $purchaseReceipt->refresh()->status);
        self::assertTrue(FinancialPosting::query()
            ->where('source_type', PurchaseReceipt::class)
            ->where('source_id', $purchaseReceipt->getKey())
            ->where('source_event', 'voided')
            ->exists());
    }

    private function order(
        PurchaseOrderStatus $status,
        int $ordered,
        int $received,
    ): PurchaseOrder {
        $order = PurchaseOrder::query()->create([
            'order_number' => 'PO-P14-'.Str::upper(Str::random(6)),
            'supplier_id' => $this->supplier->getKey(),
            'branch_id' => $this->source->getKey(),
            'created_by_account_id' => $this->actor->getKey(),
            'approved_by_account_id' => $status === PurchaseOrderStatus::Draft ? null : $this->actor->getKey(),
            'status' => $status,
            'approval_status' => $status === PurchaseOrderStatus::Draft ? 'pending' : 'approved',
            'currency' => 'NGN',
            'subtotal_kobo' => 5_000,
            'tax_kobo' => 0,
            'total_kobo' => 5_000,
            'reference_note' => 'Phase 14 lifecycle test order.',
            'approved_at' => $status === PurchaseOrderStatus::Draft ? null : now(),
        ]);
        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'ordered_quantity_milliunits' => $ordered,
            'received_quantity_milliunits' => $received,
            'cancelled_quantity_milliunits' => 0,
            'backordered_quantity_milliunits' => max(0, $ordered - $received),
            'unit_cost_kobo' => 1_000,
            'tax_rate_basis_points' => 0,
            'line_total_kobo' => 5_000,
            'landed_cost_allocated_kobo' => 0,
        ]);

        return $order->fresh(['lines']) ?? $order;
    }

    private function branch(string $code, string $name): Branch
    {
        return Branch::query()->create([
            'name' => $name,
            'code' => $code,
            'address' => 'Phase 14 test address',
            'status' => 'active',
            'is_head_office' => false,
        ]);
    }
}
