<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Customers;

use App\Models\Account;
use App\Models\Customer;
use App\Services\Customers\CustomerCodeGenerator;
use App\Services\Organisation\AuditLogger;
use App\Support\Security\EncryptedValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class QuickCustomerController
{
    public function __construct(private CustomerCodeGenerator $codes, private EncryptedValue $encrypted, private AuditLogger $audit) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var Account $actor */ $actor = $request->user();
        $v = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $customer = Customer::query()->create([
            'customer_code' => $this->codes->generate(), 'name' => trim($v['name']), 'phone' => $v['phone'] ?? null,
            'whatsapp_phone' => $v['whatsapp_phone'] ?? null, 'email_encrypted' => empty($v['email']) ? null : $this->encrypted->encrypt($v['email']),
            'address' => $v['address'] ?? null, 'notes' => $v['notes'] ?? null, 'credit_limit_kobo' => 0, 'balance_kobo' => 0,
            'is_wholesale' => false, 'status' => 'active', 'created_by_account_id' => $actor->getKey(),
        ]);
        $this->audit->record($request, 'customer.quick-created', 'customer', $customer, after: ['name' => $customer->name]);

        return response()->json(['id' => (string) $customer->getKey(), 'name' => $customer->name, 'phone' => $customer->phone, 'customer_code' => $customer->customer_code], 201);
    }
}
