<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('api.tokens.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [
                'required',
                'string',
                Rule::in([
                    '*',
                    'products.read',
                    'customers.read',
                    'sales.read',
                    'sales.create',
                    'quotes.convert',
                    'reports.read',
                ]),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
