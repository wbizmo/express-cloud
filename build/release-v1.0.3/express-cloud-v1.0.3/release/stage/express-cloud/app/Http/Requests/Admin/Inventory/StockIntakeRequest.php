<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final class StockIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'ulid'],
            'branch_id' => ['required', 'ulid'],
            'quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
