<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Accounting;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLedgerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'is_active' => ['sometimes', 'boolean'],
            'allow_manual_posting' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
