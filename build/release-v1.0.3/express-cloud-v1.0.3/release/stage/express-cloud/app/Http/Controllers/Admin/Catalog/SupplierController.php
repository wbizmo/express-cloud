<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Enums\Catalog\RecordStatus;
use App\Http\Requests\Admin\Catalog\StoreSupplierRequest;
use App\Models\Supplier;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\AuditLogger;
use App\Support\Security\EncryptedValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final readonly class SupplierController
{
    public function __construct(
        private MoneyInput $money,
        private EncryptedValue $encrypted,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.catalog.suppliers.index', [
            'suppliers' => Supplier::query()
                ->orderByDesc('is_preferred')
                ->orderBy('company_name')
                ->paginate((int) config(
                    'catalog.pagination.suppliers',
                    25,
                )),
        ]);
    }

    public function store(
        StoreSupplierRequest $request,
    ): RedirectResponse {
        $supplier = Supplier::query()->create([
            'supplier_code' => Str::upper(
                $request->string('supplier_code')->trim()->toString(),
            ),
            'company_name' => $request->string('company_name')
                ->trim()
                ->toString(),
            'contact_person' => $request->filled('contact_person')
                ? $request->string('contact_person')->trim()->toString()
                : null,
            'category' => $request->filled('category')
                ? $request->string('category')->trim()->toString()
                : null,
            'email_encrypted' => $request->filled('email')
                ? $this->encrypted->encrypt(
                    $request->string('email')->trim()->toString(),
                )
                : null,
            'phone' => $request->filled('phone')
                ? $request->string('phone')->trim()->toString()
                : null,
            'address' => $request->filled('address')
                ? $request->string('address')->trim()->toString()
                : null,
            'tax_number_encrypted' => $request->filled('tax_number')
                ? $this->encrypted->encrypt(
                    $request->string('tax_number')->trim()->toString(),
                )
                : null,
            'payment_terms_days' => $request->integer(
                'payment_terms_days',
            ),
            'credit_limit_kobo' => $this->money->toKobo(
                $request->input('credit_limit'),
            ) ?? 0,
            'lead_time_days' => $request->integer('lead_time_days'),
            'delivery_terms' => $request->filled('delivery_terms')
                ? $request->string('delivery_terms')->trim()->toString()
                : null,
            'return_policy' => $request->filled('return_policy')
                ? $request->string('return_policy')->trim()->toString()
                : null,
            'is_preferred' => $request->boolean('is_preferred'),
            'status' => 'active',
            'notes' => $request->filled('notes')
                ? $request->string('notes')->trim()->toString()
                : null,
        ]);

        $this->audit->record(
            $request,
            'supplier.created',
            'supplier',
            $supplier,
            after: [
                'supplier_code' => $supplier->supplier_code,
                'company_name' => $supplier->company_name,
                'category' => $supplier->category,
                'is_preferred' => $supplier->is_preferred,
                'status' => $supplier->status instanceof RecordStatus
                    ? $supplier->status->value
                    : (string) $supplier->status,
            ],
        );

        return back()->with('status', 'Supplier created.');
    }
}
