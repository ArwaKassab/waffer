<?php

// app/Http/Requests/UpdateProductRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'price'    => ['sometimes', 'numeric', 'min:0'],
            'status'   => ['sometimes', 'in:available,unavailable'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'unit'     => ['sometimes', 'string', 'max:50'],
            'details'  => ['sometimes', 'nullable', 'string'],
            'image'    => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
