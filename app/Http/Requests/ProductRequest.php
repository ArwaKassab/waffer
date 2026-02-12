<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'price'    => 'required|numeric|min:0',
            'image'    => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'quantity' => 'required|numeric|min:0',
            'unit'     => 'required|in:غرام,كيلوغرام,قطعة,لتر',
            'details'  => 'nullable|string',
            'store_id' => 'required|exists:users,id',
            'status'   => 'nullable|in:available,not_available',
        ];
    }
}
