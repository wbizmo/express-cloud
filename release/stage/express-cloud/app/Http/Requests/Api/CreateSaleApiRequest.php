<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateSaleApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sale_type' => [
                'required',
                Rule::in(['invoice', 'quote', 'pos']),
            ],
            'branch_id' => ['required', 'ulid', 'exists:branches,id'],
            'customer_id' => [
                'nullable',
                'ulid',
                'exists:customers,id',
            ],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => [
                'required',
                'string',
                'min:16',
                'max:120',
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'ulid',
                'exists:products,id',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'items.*.unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }
}
