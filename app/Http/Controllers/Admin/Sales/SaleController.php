<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Actions\Sales\AddSalePayment;
use App\Actions\Sales\ConvertQuote;
use App\Actions\Sales\CreateSale;
use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use App\Http\Requests\Sales\AddSalePaymentRequest;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\ProductBranchStock;
use App\Models\Sale;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class SaleController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();

        /** @var Account $actor */
        $actor = $request->user();

        return view('admin.sales.index', [
            'sales' => Sale::query()
                ->when(! $actor->can('sales.view.all'), fn ($query) => $query->where('sold_by_account_id', $actor->getKey()))
                ->when(! $actor->is_allowed_all_branches, fn ($query) => $query->whereIn('branch_id', $actor->branches()->select('branches.id')))
                ->with([
                    'branch:id,name',
                    'customer:id,name,phone',
                    'soldBy:id,first_name,last_name',
                ])
                ->when(
                    $type !== '',
                    static fn ($query) => $query->where(
                        'sale_type',
                        $type,
                    ),
                )
                ->orderByDesc('created_at')
                ->cursorPaginate(50)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.sales.create', [
            'branches' => Branch::query()
                ->where('status', 'active')
                ->when(! $request->user()->is_allowed_all_branches, fn ($query) => $query->whereIn('id', $request->user()->branches()->select('branches.id')))
                ->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderByDesc('is_default_for_pos')
                ->orderBy('name')
                ->get(['id', 'name', 'is_default_for_pos']),
            'productStocks' => ProductBranchStock::query()
                ->get(['product_id', 'branch_id', 'quantity_milliunits'])
                ->mapWithKeys(static fn (ProductBranchStock $stock): array => [
                    $stock->branch_id.'|'.$stock->product_id => $stock->quantity_milliunits,
                ]),            'productPrices' => ProductBranchStock::query()
                ->whereNotNull('selling_price_kobo')
                ->get(['product_id', 'branch_id', 'selling_price_kobo'])
                ->mapWithKeys(static fn (ProductBranchStock $stock): array => [
                    $stock->branch_id.'|'.$stock->product_id => (int) $stock->selling_price_kobo,
                ]),
        ]);
    }

    public function store(
        StoreSaleRequest $request,
        CreateSale $creator,
    ): RedirectResponse|JsonResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $sale = $creator->execute($request, $actor);

        $this->audit->record(
            $request,
            'sale.created',
            'sale',
            $sale,
            after: [
                'sale_code' => $sale->sale_code,
                'sale_type' => $sale->sale_type instanceof SaleType
                    ? $sale->sale_type->value
                    : (string) $sale->sale_type,
                'grand_total_kobo' => $sale->grand_total_kobo,
                'paid_amount_kobo' => $sale->paid_amount_kobo,
                'status' => $sale->status instanceof SaleStatus
                    ? $sale->status->value
                    : (string) $sale->status,
            ],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'id' => (string) $sale->getKey(),
                'sale_code' => $sale->sale_code,
                'status' => $sale->status instanceof SaleStatus
                    ? $sale->status->value
                    : (string) $sale->status,
            ], 201);
        }

        return redirect()
            ->route('admin.sales.show', $sale)
            ->with('status', 'Sale created.');
    }

    public function show(Sale $sale): View
    {
        return view('admin.sales.show', [
            'sale' => $sale->load([
                'branch:id,name',
                'customer:id,name,phone,address',
                'soldBy:id,first_name,last_name',
                'items.product:id,name,sku',
                'payments.paymentMethod:id,name',
            ]),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function addPayment(
        AddSalePaymentRequest $request,
        Sale $sale,
        AddSalePayment $action,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        /** @var PaymentMethod $method */
        $method = PaymentMethod::query()->findOrFail(
            $request->string('payment_method_id')->toString(),
        );

        $payment = $action->execute(
            $sale,
            $method,
            $actor,
            $request->input('amount'),
            $request->filled('reference')
                ? $request->string('reference')->trim()->toString()
                : null,
        );

        $this->audit->record(
            $request,
            'sale.payment-recorded',
            'payment',
            $payment,
            after: [
                'sale_id' => (string) $sale->getKey(),
                'amount_kobo' => $payment->amount_kobo,
                'payment_method_id' => (string) $method->getKey(),
            ],
        );

        return back()->with('status', 'Payment recorded.');
    }

    public function convert(
        StoreSaleRequest $request,
        Sale $quote,
        ConvertQuote $converter,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        $sale = $converter->execute(
            $quote,
            $request,
            $actor,
        );

        $this->audit->record(
            $request,
            'quote.converted',
            'sale',
            $sale,
            after: [
                'converted_from_sale_id' => (string) $quote->getKey(),
                'sale_code' => $sale->sale_code,
            ],
        );

        return redirect()
            ->route('admin.sales.show', $sale)
            ->with('status', 'Quote converted successfully.');
    }
}
