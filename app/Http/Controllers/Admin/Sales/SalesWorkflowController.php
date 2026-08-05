<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Enums\Sales\SaleType;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Sale;
use App\Services\Performance\StreamedCsvExport;
use App\Services\Sales\SalesWorkflowEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class SalesWorkflowController
{
    public function __construct(
        private SalesWorkflowEngine $sales,
        private StreamedCsvExport $exports,
    ) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $query = Sale::query()
            ->with(['customer:id,name,customer_code', 'branch:id,name'])
            ->whereIn('sale_type', [
                SaleType::Quote->value,
                SaleType::Order->value,
                SaleType::Invoice->value,
            ])
            ->when(
                ! $actor->is_allowed_all_branches,
                static fn ($builder) => $builder->whereIn(
                    'branch_id',
                    $actor->branches()->select('branches.id'),
                ),
            );
        $type = $request->string('type')->toString();
        if (in_array($type, array_column(SaleType::cases(), 'value'), true)) {
            $query->where('sale_type', $type);
        }

        /** @var view-string $viewName */
        $viewName = 'admin.sales.workflows';

        return view($viewName, [
            'documents' => $query->orderByDesc('created_at')
                ->cursorPaginate(config('pagination.default', 10))
                ->withQueryString(),
            'groups' => CustomerGroup::query()->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->limit(100)->get(),
            'selectedType' => $type,
        ]);
    }

    public function convert(Request $request, Sale $sale): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:120'],
            'target_type' => ['required', 'in:order,invoice'],
            'memo' => ['required', 'string', 'max:3000'],
        ]);
        $converted = $this->sales->convert(
            $sale,
            SaleType::from((string) $validated['target_type']),
            $actor,
            (string) $validated['idempotency_key'],
            (string) $validated['memo'],
        );

        return redirect()->route('admin.sales.show', $converted)
            ->with('status', 'Sales document converted successfully.');
    }

    public function deliver(Request $request, Sale $sale): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'ulid'],
            'memo' => ['required', 'string', 'max:3000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sale_item_id' => ['required', 'ulid'],
            'lines.*.quantity_milliunits' => ['required', 'integer', 'min:1'],
        ]);
        /** @var list<array{sale_item_id: string, quantity_milliunits: int}> $lines */
        $lines = $validated['lines'];
        $this->sales->deliver(
            $sale,
            $actor,
            $lines,
            isset($validated['warehouse_id']) ? (string) $validated['warehouse_id'] : null,
            (string) $validated['memo'],
        );

        return back()->with('status', 'Delivery dispatch recorded.');
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $query = Sale::query()->when(
            ! $actor->is_allowed_all_branches,
            static fn ($builder) => $builder->whereIn(
                'branch_id',
                $actor->branches()->select('branches.id'),
            ),
        );

        return $this->exports->download(
            $query,
            ['Code', 'Type', 'Date', 'Status', 'Total Kobo', 'Paid Kobo'],
            static fn (object $sale): array => [
                $sale->sale_code,
                $sale->sale_type instanceof SaleType ? $sale->sale_type->value : (string) $sale->sale_type,
                (string) $sale->sale_date,
                (string) ($sale->status instanceof \BackedEnum ? $sale->status->value : $sale->status),
                (int) $sale->grand_total_kobo,
                (int) $sale->paid_amount_kobo,
            ],
            'sales-documents-'.now()->format('Ymd-His').'.csv',
        );
    }
}
