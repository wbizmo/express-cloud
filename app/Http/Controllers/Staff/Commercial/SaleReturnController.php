<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff\Commercial;

use App\Actions\Commercial\CreateSaleReturn;
use App\Http\Requests\Commercial\StoreSaleReturnRequest;
use App\Models\Account;
use App\Models\Sale;
use App\Services\Commercial\SaleAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class SaleReturnController
{
    public function __construct(
        private SaleAccess $access,
        private CreateSaleReturn $returns,
    ) {}

    public function create(
        StoreSaleReturnRequest $request,
        Sale $sale,
    ): View {
        /** @var Account $actor */
        $actor = $request->user();
        abort_unless($this->access->canView($actor, $sale), 403);

        return view('staff.commercial.sale-return', [
            'sale' => $sale->load('items'),
        ]);
    }

    public function store(
        StoreSaleReturnRequest $request,
        Sale $sale,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        abort_unless($this->access->canView($actor, $sale), 403);

        $return = $this->returns->execute(
            $request,
            $sale,
            $actor,
            $request->array('items'),
            $request->string('reason')->toString(),
            $request->filled('refund_method')
                ? $request->string('refund_method')->toString()
                : null,
            $request->string('idempotency_key')->trim()->toString(),
        );

        return redirect()
            ->route('staff.sales.show', $sale)
            ->with(
                'status',
                "Return {$return->return_code} recorded.",
            );
    }
}
