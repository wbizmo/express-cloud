<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountingOperations;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStandaloneReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('receipts.create') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'ulid', 'exists:branches,id'],
            'customer_id' => [
                'nullable',
                'ulid',
                'exists:customers,id',
            ],
            'payment_method_id' => [
                'required',
                'ulid',
                'exists:payment_methods,id',
            ],
            'payer_name' => ['required', 'string', 'max:180'],
            'payer_phone' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:180'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'received_at' => ['required', 'date'],
        ];
    }
}
