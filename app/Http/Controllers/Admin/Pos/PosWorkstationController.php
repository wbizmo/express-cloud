<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Pos;

use App\Enums\Sales\SaleType;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Account;
use App\Models\CommercialApprovalRequest;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\PosHeldSale;
use App\Models\PosShift;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Pos\PosShiftService;
use App\Services\Sales\SalesWorkflowEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class PosWorkstationController
{
    public function __construct(
        private PosShiftService $shifts,
        private SalesWorkflowEngine $sales,
    ) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $branchIds = $actor->is_allowed_all_branches
            ? null
            : $actor->branches()->pluck('branches.id');
        $openShift = PosShift::query()
            ->where('cashier_account_id', $actor->getKey())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        /** @var view-string $viewName */
        $viewName = 'admin.pos.workstation';

        return view($viewName, [
            'openShift' => $openShift,
            'terminals' => PosTerminal::query()
                ->when($branchIds !== null, static fn ($query) => $query->whereIn('branch_id', $branchIds))
                ->where('status', 'active')->orderBy('name')->get(),
            'methods' => PaymentMethod::query()
                ->where('is_active', true)->where('is_visible_in_pos', true)
                ->orderByDesc('is_default_for_pos')->orderBy('name')->get(),
            'products' => Product::query()->where('status', 'active')
                ->orderBy('name')->cursorPaginate(config('pagination.default', 10)),
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->limit(100)->get(),
            'heldSales' => $openShift instanceof PosShift
                ? PosHeldSale::query()->where('pos_shift_id', $openShift->getKey())
                    ->where('status', 'held')->latest('held_at')->limit(30)->get()
                : collect(),
        ]);
    }

    public function open(Request $request, PosTerminal $terminal): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate(['opening_float_kobo' => ['required', 'integer', 'min:0']]);
        $this->shifts->open($terminal, $actor, (int) $validated['opening_float_kobo']);

        return back()->with('status', 'POS shift opened.');
    }

    public function complete(StoreSaleRequest $request, PosShift $shift): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        if (! $shift->isOpen() || (string) $shift->cashier_account_id !== (string) $actor->getKey()) {
            throw new \DomainException('The sale requires the cashier’s active POS shift.');
        }
        if ($request->string('sale_type')->toString() !== SaleType::Pos->value) {
            throw new \DomainException('The POS workstation only completes POS sales.');
        }
        $sale = $this->sales->create($request, $actor);
        if ((string) $sale->pos_shift_id !== (string) $shift->getKey()) {
            $sale->forceFill([
                'pos_shift_id' => $shift->getKey(),
                'pos_terminal_id' => $shift->pos_terminal_id,
            ])->save();
        }

        return redirect()->route('admin.sales.show', $sale)
            ->with('status', 'POS sale completed and confirmed.');
    }

    public function cash(Request $request, PosShift $shift): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'movement_type' => ['required', 'in:pay_in,pay_out,cash_refund'],
            'amount_kobo' => ['required', 'integer', 'min:1'],
            'memo' => ['required', 'string', 'max:2000'],
        ]);
        $this->shifts->recordMovement(
            $shift,
            $actor,
            (string) $validated['movement_type'],
            (int) $validated['amount_kobo'],
            (string) $validated['memo'],
        );

        return back()->with('status', 'Cash movement recorded.');
    }

    public function hold(Request $request, PosShift $shift): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'cart' => ['required', 'array', 'min:1'],
            'estimated_total_kobo' => ['required', 'integer', 'min:0'],
            'customer_id' => ['nullable', 'ulid'],
        ]);
        $this->shifts->hold(
            $shift,
            $actor,
            $validated['cart'],
            (int) $validated['estimated_total_kobo'],
            isset($validated['customer_id']) ? (string) $validated['customer_id'] : null,
        );

        return back()->with('status', 'Cart held for later recovery.');
    }

    public function resume(PosShift $shift, PosHeldSale $heldSale): RedirectResponse
    {
        $held = $this->shifts->resume($heldSale, $shift);

        return back()->with('status', 'Held cart resumed: '.$held->hold_token);
    }

    public function close(Request $request, PosShift $shift): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'tenders' => ['required', 'array'],
            'tenders.*' => ['integer', 'min:0'],
            'note' => ['required', 'string', 'max:3000'],
            'variance_approval_id' => ['nullable', 'ulid'],
        ]);
        $approval = isset($validated['variance_approval_id'])
            ? CommercialApprovalRequest::query()->find((string) $validated['variance_approval_id'])
            : null;
        /** @var array<string, int> $tenders */
        $tenders = $validated['tenders'];
        $this->shifts->close($shift, $actor, $tenders, (string) $validated['note'], $approval);

        return back()->with('status', 'POS shift closed and reconciled.');
    }

    public function print(Request $request, Sale $sale): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $validated = $request->validate([
            'format' => ['required', 'in:58mm,80mm,a4'],
            'pos_shift_id' => ['nullable', 'ulid'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'approval_id' => ['nullable', 'ulid'],
        ]);
        $shift = isset($validated['pos_shift_id'])
            ? PosShift::query()->find((string) $validated['pos_shift_id'])
            : null;
        $approval = isset($validated['approval_id'])
            ? CommercialApprovalRequest::query()->find((string) $validated['approval_id'])
            : null;
        $this->shifts->recordPrint(
            $sale,
            $actor,
            (string) $validated['format'],
            $shift,
            isset($validated['reason']) ? (string) $validated['reason'] : null,
            $approval,
        );

        return redirect()->route('admin.sales.documents.receipt', $sale);
    }
}
