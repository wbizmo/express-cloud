<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\Authorization\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:2000'],
            'permissions' => ['array'],
            'permissions.*' => [
                'string',
                Rule::in(PermissionCatalog::all()),
            ],
        ];
    }
}
