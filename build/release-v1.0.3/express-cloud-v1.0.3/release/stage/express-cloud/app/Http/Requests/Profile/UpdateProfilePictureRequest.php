<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfilePictureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public function rules(): array
    {
        return [
            'profile_picture' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config(
                    'authentication.profile_picture.maximum_kilobytes',
                    2048,
                ),
                'dimensions:min_width=128,min_height=128,max_width=4096,max_height=4096',
            ],
        ];
    }
}
