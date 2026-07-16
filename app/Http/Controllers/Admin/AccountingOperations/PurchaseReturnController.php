<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AccountingOperations;

use App\Actions\AccountingOperations\CreatePurchaseReturn;
use App\Http\Requests\AccountingOperations\StorePurchaseReturnRequest;
use App\Models\Account;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class PurchaseReturnController
{
    public function __construct(
        private CreatePurchaseReturn $create,
    ) {}

    public function index(): View
    {
        return view('admin.accounting-operations.purchase-returns', [
            'returns' => PurchaseReturn::query()
                ->orderByDesc('returned_at')
                ->paginate(40),
        ]);
    }

    public function create(): View
    {
        return view(
            'admin.accounting-operations.purchase-return-create',
            [
                'purchases' => PurchaseReceipt::query()
                    ->with('lines')
                    ->orderByDesc('purchased_at')
                    ->limit(200)
                    ->get(),
            ],
        );
    }

    public function store(
        StorePurchaseReturnRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        /** @var PurchaseReceipt $purchase */
        $purchase = PurchaseReceipt::query()->findOrFail(
            $request->string(
                'purchase_receipt_id',
            )->toString(),
        );

        $return = $this->create->execute(
            $request,
            $purchase,
            $actor,
            $request->array('lines'),
            $request->string('reason')->toString(),
            $request->filled('supplier_credit_reference')
                ? $request->string(
                    'supplier_credit_reference',
                )->toString()
                : null,
        );

        return redirect()
            ->route(
                'admin.accounting-operations.purchase-returns.index',
            )
            ->with(
                'status',
                "Purchase return {$return->return_number} recorded.",
            );
    }
}
