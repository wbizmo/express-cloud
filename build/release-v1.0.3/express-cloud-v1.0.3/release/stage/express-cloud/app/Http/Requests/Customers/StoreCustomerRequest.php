<?php

declare(strict_types=1);

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'address' => ['nullable', 'string', 'max:3000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_wholesale' => ['sometimes', 'boolean'],
        ];
    }
}
