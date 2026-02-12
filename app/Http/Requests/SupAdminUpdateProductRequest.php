<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupAdminUpdateProductRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'name'     => 'sometimes|string|max:255',
            'price'    => 'sometimes|numeric|min:0',
            'image'    => 'sometimes|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'quantity' => 'sometimes|numeric|min:0',
            'unit'     => 'sometimes|in:غرام,كيلوغرام,قطعة,لتر',
            'details'  => 'sometimes|string|nullable',
        ];
    }

}
