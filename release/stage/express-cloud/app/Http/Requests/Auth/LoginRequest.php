<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'account_public_id' => [
                'required',
                'uuid',
            ],
            'access_key' => [
                'required',
                'string',
                'regex:/^[A-HJ-KM-NP-Z2-9]{4}-?[A-HJ-KM-NP-Z2-9]{4}$/i',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_public_id.required' => 'Select your staff name.',
            'access_key.required' => 'Enter your access key.',
            'access_key.regex' => 'Enter the complete access key.',
        ];
    }
}
