<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['ulid'],
            'is_allowed_all_branches' => ['sometimes', 'boolean'],
            'branch_ids' => ['array'],
            'branch_ids.*' => ['ulid'],
        ];
    }
}
