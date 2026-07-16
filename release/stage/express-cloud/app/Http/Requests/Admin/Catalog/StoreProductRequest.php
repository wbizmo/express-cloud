<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, int|string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:100', 'alpha_dash'],
            'barcode' => ['nullable', 'string', 'max:160'],
            'category_id' => ['required', 'ulid'],
            'brand_id' => ['nullable', 'ulid'],
            'tax_rate_id' => ['nullable', 'ulid'],
            'description' => ['nullable', 'string', 'max:5000'],
            'track_inventory' => ['sometimes', 'boolean'],
            'default_price' => ['required', 'numeric', 'min:0'],
            'default_cost_price' => ['nullable', 'numeric', 'min:0'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('catalog.images.maximum_kilobytes', 4096),
            ],
            'branch_prices' => ['array'],
            'branch_prices.*.branch_id' => ['required', 'ulid'],
            'branch_prices.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
