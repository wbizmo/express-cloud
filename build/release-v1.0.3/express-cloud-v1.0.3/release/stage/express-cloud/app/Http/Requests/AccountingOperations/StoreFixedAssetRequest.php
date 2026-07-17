<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountingOperations;

use Illuminate\Foundation\Http\FormRequest;

final class StoreFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:120'],
            'branch_id' => ['nullable', 'ulid', 'exists:branches,id'],
            'custodian_account_id' => [
                'nullable',
                'ulid',
                'exists:accounts,id',
            ],
            'acquired_at' => ['required', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => [
                'required',
                'integer',
                'min:1',
                'max:1200',
            ],
            'serial_number' => ['nullable', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
