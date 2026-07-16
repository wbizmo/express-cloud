<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Procurement;

use Illuminate\Foundation\Http\FormRequest;

final class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, int|string>> */
    public function rules(): array
    {
        return [
            'reference_note' => ['required', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'ulid'],
            'lines.*.quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
        ];
    }
}
