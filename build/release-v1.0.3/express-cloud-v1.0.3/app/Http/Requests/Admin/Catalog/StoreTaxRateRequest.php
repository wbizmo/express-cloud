<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
