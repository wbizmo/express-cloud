<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SupplierFinance;

use Illuminate\Foundation\Http\FormRequest;

final class RecordSupplierBillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'ulid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:160'],
        ];
    }
}
