<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SupplierFinance;

use App\Actions\SupplierFinance\CreateSupplierBill;
use App\Actions\SupplierFinance\RecordSupplierBillPayment;
use App\Actions\SupplierFinance\StoreSupplierDocument;
use App\Enums\SupplierFinance\SupplierBillStatus;
use App\Http\Requests\Admin\SupplierFinance\RecordSupplierBillPaymentRequest;
use App\Http\Requests\Admin\SupplierFinance\StoreSupplierBillRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class SupplierBillController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.supplier-finance.bills-index', [
            'bills' => SupplierBill::query()
                ->with([
                    'supplier:id,company_name,supplier_code',
                    'branch:id,name',
                ])
                ->orderByDesc('bill_date')
                ->orderByDesc('created_at')
                ->cursorPaginate((int) config(
                    'supplier-finance.pagination.bills',
                    40,
                )),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'supplier_code']),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'purchaseOrders' => PurchaseOrder::query()
                ->whereIn('status', [
                    'approved',
                    'partially_received',
                    'received',
                ])
                ->orderByDesc('created_at')
                ->limit(100)
                ->get([
                    'id',
                    'order_number',
                    'supplier_id',
                    'branch_id',
                ]),
            'products' => Product::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(
        StoreSupplierBillRequest $request,
        CreateSupplierBill $creator,
        StoreSupplierDocument $documents,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $bill = $creator->execute(
            $actor,
            $request->string('supplier_id')->toString(),
            $request->string('branch_id')->toString(),
            $request->filled('purchase_order_id')
                ? $request->string('purchase_order_id')->toString()
                : null,
            $request->string('bill_date')->toString(),
            $request->filled('due_date')
                ? $request->string('due_date')->toString()
                : null,
            $request->filled('supplier_reference')
                ? $request->string('supplier_reference')->trim()->toString()
                : null,
            $request->string('reference_note')->trim()->toString(),
            $request->array('lines'),
        );

        if ($request->hasFile('attachment')) {
            $documents->execute(
                $request->file('attachment'),
                (string) $bill->supplier_id,
                $bill,
                $actor,
                $request->filled('attachment_description')
                    ? $request->string(
                        'attachment_description',
                    )->trim()->toString()
                    : null,
            );
        }

        $this->audit->record(
            $request,
            'supplier-bill.created',
            'supplier_bill',
            $bill,
            after: [
                'bill_number' => $bill->bill_number,
                'supplier_id' => (string) $bill->supplier_id,
                'total_kobo' => $bill->total_kobo,
                'status' => $bill->status
                    instanceof SupplierBillStatus
                    ? $bill->status->value
                    : (string) $bill->status,
            ],
        );

        return redirect()
            ->route('admin.supplier-finance.bills.show', $bill)
            ->with('status', 'Supplier bill created.');
    }

    public function show(SupplierBill $bill): View
    {
        return view('admin.supplier-finance.bill-show', [
            'bill' => $bill->load([
                'supplier:id,company_name,supplier_code',
                'branch:id,name',
                'purchaseOrder:id,order_number',
                'lines.product:id,name,sku',
                'payments.paymentMethod:id,name',
                'documents',
            ]),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function pay(
        RecordSupplierBillPaymentRequest $request,
        SupplierBill $bill,
        RecordSupplierBillPayment $payments,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        /** @var PaymentMethod $method */
        $method = PaymentMethod::query()->findOrFail(
            $request->string('payment_method_id')->toString(),
        );

        $payment = $payments->execute(
            $bill,
            $method,
            $actor,
            $request->input('amount'),
            $request->filled('reference')
                ? $request->string('reference')->trim()->toString()
                : null,
        );

        $this->audit->record(
            $request,
            'supplier-bill.payment-recorded',
            'supplier_bill_payment',
            $payment,
            after: [
                'supplier_bill_id' => (string) $bill->getKey(),
                'amount_kobo' => $payment->amount_kobo,
                'payment_method_id' => (string) $method->getKey(),
            ],
        );

        return back()->with('status', 'Supplier payment recorded.');
    }

    public function downloadDocument(
        Request $request,
        SupplierBill $bill,
        string $document,
    ): BinaryFileResponse {
        $record = $bill->documents()->findOrFail($document);
        $disk = (string) config(
            'supplier-finance.attachments.disk',
            'local',
        );

        abort_unless(
            Storage::disk($disk)->exists($record->stored_path),
            404,
        );

        $this->audit->record(
            $request,
            'supplier-document.downloaded',
            'supplier_document',
            $record,
            after: [
                'supplier_bill_id' => (string) $bill->getKey(),
                'original_filename' => $record->original_filename,
            ],
        );

        return response()->download(
            Storage::disk($disk)->path($record->stored_path),
            $record->original_filename,
        );
    }
}
