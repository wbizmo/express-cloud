<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountingOperations;

use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchase_returns.create') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'purchase_receipt_id' => [
                'required',
                'ulid',
                'exists:purchase_receipts,id',
            ],
            'supplier_credit_reference' => [
                'nullable',
                'string',
                'max:180',
            ],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_receipt_line_id' => [
                'required',
                'ulid',
                'exists:purchase_receipt_lines,id',
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
