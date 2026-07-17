<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Imports;

use Illuminate\Foundation\Http\FormRequest;

final class UploadProductImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, int|string>> */
    public function rules(): array
    {
        return [
            'workbook' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:'.config(
                    'imports.products.maximum_kilobytes',
                    10240,
                ),
            ],
        ];
    }
}
