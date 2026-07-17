<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountingOperations;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDocumentBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('documents.branding.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:180'],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
            'remove_logo' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:180'],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
            'document_terms' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
