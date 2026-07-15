<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Enums\Sales\SaleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:80'],
            'sale_type' => [
                'required',
                Rule::enum(SaleType::class),
            ],
            'branch_id' => ['required', 'ulid'],
            'customer_id' => ['nullable', 'ulid'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'ulid'],
            'items.*.quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['array'],
            'payments.*.payment_method_id' => ['required', 'ulid'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:160'],
        ];
    }
}
