<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

final class StoreClassificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:140'],
            'slug' => ['required', 'string', 'max:160', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
