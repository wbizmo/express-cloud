<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Procurement;

use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, int|string>> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'ulid'],
            'branch_id' => ['required', 'ulid'],
            'expected_at' => ['nullable', 'date'],
            'reference_note' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'ulid'],
            'lines.*.quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
