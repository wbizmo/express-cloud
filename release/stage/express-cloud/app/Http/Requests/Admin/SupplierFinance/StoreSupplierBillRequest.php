<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SupplierFinance;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierBillRequest extends FormRequest
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
            'purchase_order_id' => ['nullable', 'ulid'],
            'supplier_reference' => ['nullable', 'string', 'max:160'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:bill_date'],
            'reference_note' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'ulid'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => [
                'required',
                'regex:/^\d+(?:\.\d{1,3})?$/',
            ],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:'.config(
                    'supplier-finance.attachments.maximum_kilobytes',
                    10240,
                ),
                'mimes:pdf,jpg,jpeg,png,webp,xlsx,docx',
            ],
            'attachment_description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
