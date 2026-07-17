<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'supplier_code' => ['required', 'string', 'max:60', 'alpha_dash'],
            'company_name' => ['required', 'string', 'max:180'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:3000'],
            'tax_number' => ['nullable', 'string', 'max:160'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'delivery_terms' => ['nullable', 'string', 'max:3000'],
            'return_policy' => ['nullable', 'string', 'max:3000'],
            'is_preferred' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
