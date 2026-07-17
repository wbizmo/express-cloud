<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash'],
            'address' => ['required', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_head_office' => ['sometimes', 'boolean'],
        ];
    }
}
