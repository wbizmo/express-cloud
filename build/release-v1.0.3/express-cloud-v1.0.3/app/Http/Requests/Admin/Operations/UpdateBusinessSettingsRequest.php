<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Operations;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:150'],
            'head_office_address' => [
                'required',
                'string',
                'max:3000',
            ],
            'end_of_day_digest_time' => [
                'required',
                'date_format:H:i',
            ],
            'session_inactivity_minutes' => [
                'required',
                'integer',
                'min:5',
                'max:240',
            ],
            'business_logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:4096',
            ],
        ];
    }
}
