<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\StoreTaxRateRequest;
use App\Models\TaxRate;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class TaxRateController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.catalog.tax-rates.index', [
            'records' => TaxRate::query()
                ->withCount('products')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->paginate((int) config(
                    'catalog.pagination.classifications',
                    50,
                )),
        ]);
    }

    public function store(
        StoreTaxRateRequest $request,
    ): RedirectResponse {
        $record = DB::transaction(function () use ($request): TaxRate {
            if ($request->boolean('is_default')) {
                TaxRate::query()->update(['is_default' => false]);
            }

            return TaxRate::query()->create([
                'name' => $request->string('name')->trim()->toString(),
                'rate_basis_points' => (int) round(
                    $request->float('rate_percent') * 100,
                ),
                'status' => 'active',
                'is_default' => $request->boolean('is_default'),
            ]);
        });

        $this->audit->record(
            $request,
            'tax-rate.created',
            'tax_rate',
            $record,
            after: $record->toArray(),
        );

        return back()->with('status', 'Tax rate created.');
    }
}
