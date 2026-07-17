<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SupplierFinance;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'ulid'],
            'branch_id' => ['required', 'ulid'],
            'supplier_bill_id' => ['nullable', 'ulid'],
            'reason' => ['required', 'string', 'max:120'],
            'reference_note' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'ulid'],
            'lines.*.quantity' => [
                'required',
                'regex:/^\d+(?:\.\d{1,3})?$/',
            ],
            'lines.*.unit_cost_kobo' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
