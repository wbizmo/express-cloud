<?php

declare(strict_types=1);

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.record') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'ulid', 'exists:suppliers,id'],
            'branch_id' => ['required', 'ulid', 'exists:branches,id'],
            'purchased_at' => ['required', 'date'],
            'supplier_reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                'ulid',
                'exists:products,id',
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
