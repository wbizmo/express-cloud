<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'accounting_period_id' => ['required', 'exists:accounting_periods,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'string', 'max:40'],
            'source_event' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['draft', 'posted', 'reversed'])],
            'memo' => ['required', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.ledger_account_id' => ['required', 'exists:ledger_accounts,id'],
            'lines.*.branch_id' => ['nullable', 'exists:branches,id'],
            'lines.*.customer_id' => ['nullable', 'exists:customers,id'],
            'lines.*.supplier_id' => ['nullable', 'exists:suppliers,id'],
            'lines.*.debit_kobo' => ['nullable', 'integer', 'min:0'],
            'lines.*.credit_kobo' => ['nullable', 'integer', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lines.min' => 'At least two journal lines are required.',
            'lines.*.debit_kobo.min' => 'Debit must be a positive amount.',
            'lines.*.credit_kobo.min' => 'Credit must be a positive amount.',
        ];
    }
}
