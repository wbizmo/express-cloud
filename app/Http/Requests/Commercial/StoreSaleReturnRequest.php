<?php

declare(strict_types=1);

namespace App\Http\Requests\Commercial;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.returns.create') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'refund_method' => ['nullable', 'string', 'max:80'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => [
                'required',
                'ulid',
                'exists:sale_items,id',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.restock' => ['nullable', 'boolean'],
        ];
    }
}
