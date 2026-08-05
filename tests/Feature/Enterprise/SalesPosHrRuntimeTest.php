<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Enums\Sales\SaleType;
use App\Models\Account;
use App\Models\AdminChangeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FinancialPosting;
use App\Models\JournalEntry;
use App\Models\PaymentMethod;
use App\Models\PosHeldSale;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Governance\AdminChangeService;
use App\Services\Hr\HrAdministrationService;
use App\Services\Pos\PosShiftService;
use App\Services\Sales\SalesWorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SalesPosHrRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_order_conversion_is_idempotent_and_non_financial(): void
    {
        [$actor, $branch] = $this->actorAndBranch('SALES');
        $category = ProductCategory::query()->create([
            'name' => 'Commercial Runtime',
            'slug' => 'commercial-runtime',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'name' => 'Commercial Product',
            'sku' => 'COM-001',
            'category_id' => $category->getKey(),
            'track_inventory' => false,
            'default_price_kobo' => 5_000,
            'default_cost_price_kobo' => 2_000,
            'status' => 'active',
        ]);
        $quote = Sale::query()->create([
            'sale_code' => 'QUO-RUNTIME-001',
            'sale_type' => SaleType::Quote,
            'branch_id' => $branch->getKey(),
            'sold_by_account_id' => $actor->getKey(),
            'sale_date' => today(),
            'subtotal_kobo' => 5_000,
            'discount_amount_kobo' => 0,
            'tax_amount_kobo' => 0,
            'grand_total_kobo' => 5_000,
            'paid_amount_kobo' => 0,
            'status' => 'draft',
            'idempotency_key' => 'quote-runtime',
            'workflow_state' => 'draft',
            'fulfilment_status' => 'not_required',
        ]);
        SaleItem::query()->create([
            'sale_id' => $quote->getKey(),
            'product_id' => $product->getKey(),
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'track_inventory_snapshot' => false,
            'quantity_milliunits' => 1_000,
            'unit_price_kobo' => 5_000,
            'unit_cost_kobo_snapshot' => 2_000,
            'discount_amount_kobo' => 0,
            'tax_amount_kobo' => 0,
            'line_total_kobo' => 5_000,
        ]);

        $engine = app(SalesWorkflowEngine::class);
        $first = $engine->convert($quote, SaleType::Order, $actor, 'runtime-quote-order', 'Customer accepted quote');
        $second = $engine->convert($quote, SaleType::Order, $actor, 'runtime-quote-order', 'Customer accepted quote');

        self::assertSame($first->getKey(), $second->getKey());
        self::assertSame(SaleType::Order, $first->sale_type);
        self::assertSame('converted', $quote->refresh()->workflow_state);
        self::assertSame(1, FinancialPosting::query()->where('source_id', $first->getKey())->count());
        self::assertSame(0, JournalEntry::query()->where('source_id', $first->getKey())->count());
    }

    public function test_pos_shift_hold_resume_cash_and_tender_reconciliation(): void
    {
        [$actor, $branch] = $this->actorAndBranch('POS');
        $cash = PaymentMethod::query()->create([
            'name' => 'Runtime Cash',
            'is_system_default' => false,
            'is_default_for_pos' => true,
            'is_active' => true,
            'method_type' => 'cash',
            'is_visible_in_pos' => true,
        ]);
        $terminal = PosTerminal::query()->create([
            'branch_id' => $branch->getKey(),
            'code' => 'POS-RUNTIME',
            'name' => 'Runtime Terminal',
            'printer_profile' => '80mm',
            'status' => 'active',
        ]);
        $service = app(PosShiftService::class);
        $shift = $service->open($terminal, $actor, 10_000);
        $held = $service->hold($shift, $actor, [['sku' => 'A', 'quantity' => 1]], 5_000, null);
        $resumed = $service->resume($held, $shift);
        $service->recordMovement($shift, $actor, 'pay_in', 2_000, 'Additional float');
        $closed = $service->close(
            $shift,
            $actor,
            [(string) $cash->getKey() => 12_000],
            'Counted and reconciled',
        );

        self::assertSame('resumed', $resumed->status);
        self::assertSame('closed', $closed->status);
        self::assertSame(12_000, $closed->declared_cash_kobo);
        self::assertSame(0, $closed->cash_variance_kobo);
        self::assertSame(1, PosHeldSale::query()->count());
    }

    public function test_hr_attendance_and_maker_checker_are_controlled(): void
    {
        [$maker, $branch] = $this->actorAndBranch('HR-MAKER');
        $checker = $this->account('HR Checker');
        $hr = app(HrAdministrationService::class);
        $employee = $hr->createEmployee([
            'employee_code' => 'EMP-RUNTIME',
            'branch_id' => $branch->getKey(),
            'first_name' => 'Runtime',
            'last_name' => 'Employee',
            'employment_type' => 'full_time',
        ], $maker, 'runtime-employee');
        $attendance = $hr->recordAttendance(
            $employee,
            $maker,
            today()->toDateString(),
            now()->startOfDay()->addHours(8)->toDateTimeString(),
            now()->startOfDay()->addHours(17)->toDateTimeString(),
            'present',
            null,
        );
        $department = Department::query()->create([
            'code' => 'DEP-RUNTIME',
            'name' => 'Before Approval',
            'branch_id' => $branch->getKey(),
            'status' => 'active',
        ]);
        $changes = app(AdminChangeService::class);
        $request = $changes->submit(
            Department::class,
            (string) $department->getKey(),
            'update',
            ['name' => 'After Approval'],
            $maker,
            'Rename department after review',
        );
        $decided = $changes->decide($request, $checker, true, 'Independent review completed');

        self::assertSame(540, $attendance->worked_minutes);
        self::assertSame('approved', $decided->status);
        self::assertSame('After Approval', $department->refresh()->name);
        self::assertSame(1, AdminChangeRequest::query()->count());
    }

    public function test_high_volume_employee_pagination_stays_within_query_budget(): void
    {
        [, $branch] = $this->actorAndBranch('PERF');
        for ($index = 1; $index <= 120; $index++) {
            Employee::query()->create([
                'employee_code' => sprintf('PERF-%03d', $index),
                'branch_id' => $branch->getKey(),
                'first_name' => 'Employee',
                'last_name' => sprintf('%03d', $index),
                'employment_type' => 'full_time',
                'status' => 'active',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $page = Employee::query()->orderBy('id')->cursorPaginate(10);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        self::assertCount(10, $page->items());
        self::assertLessThanOrEqual((int) config('performance.query_budget', 20), count($queries));
    }

    /** @return array{Account, Branch} */
    private function actorAndBranch(string $suffix): array
    {
        return [$this->account($suffix.' Actor'), Branch::query()->create([
            'name' => $suffix.' Branch',
            'code' => substr(str_replace('-', '', $suffix), 0, 10).Str::upper(Str::random(3)),
            'address' => 'Runtime address',
            'status' => 'active',
            'is_head_office' => true,
        ])];
    }

    private function account(string $name): Account
    {
        [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'User');

        return Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => $first,
            'last_name' => $last,
            'login_key_encrypted' => 'runtime-ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => true,
        ]);
    }
}
