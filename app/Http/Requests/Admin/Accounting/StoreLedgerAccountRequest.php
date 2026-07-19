<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLedgerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:ledger_accounts,code'],
            'name' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'parent_id' => ['nullable', 'string', 'exists:ledger_accounts,id'],
            'is_control_account' => ['sometimes', 'boolean'],
            'allow_manual_posting' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'opening_balance_kobo' => ['nullable', 'integer'],
        ];
    }
}
