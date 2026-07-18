<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkPriceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['add', 'subtract'])],
            'mode' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'all_branches' => ['nullable', 'boolean'], 'branch_ids' => ['array'], 'branch_ids.*' => ['ulid', 'exists:branches,id'],
            'all_products' => ['nullable', 'boolean'], 'product_ids' => ['array'], 'product_ids.*' => ['ulid', 'exists:products,id'],
        ];
    }
}
