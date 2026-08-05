<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Inventory;

use App\Enums\Inventory\AdjustmentReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:120'],
            'product_id' => ['required', 'ulid'],
            'branch_id' => ['required', 'ulid'],
            'quantity_delta' => [
                'required',
                'regex:/^-?\\d+(?:\\.\\d{1,3})?$/',
                'not_in:0,0.0,0.00,0.000',
            ],
            'reason_code' => [
                'required',
                Rule::in(AdjustmentReason::values()),
            ],
            'reference_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
