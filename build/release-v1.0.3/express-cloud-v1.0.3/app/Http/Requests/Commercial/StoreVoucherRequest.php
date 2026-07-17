<?php

declare(strict_types=1);

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vouchers.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique('discount_vouchers', 'code')
                    ->ignore($this->route('voucher')),
            ],
            'name' => ['required', 'string', 'max:160'],
            'value_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'value' => ['required', 'numeric', 'gt:0'],
            'minimum_sale' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
