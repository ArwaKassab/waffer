<?php

// app/Http/Requests/ProductRequestUpdatePending.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequestUpdatePending extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes','string','max:255'],
            'price'    => ['sometimes','numeric','min:0'],
            'status'   => ['sometimes','in:available,unavailable'],
            'quantity' => ['sometimes','integer','min:0'],
            'unit'     => ['sometimes','string','max:50'],
            'image'    => ['sometimes','nullable','string'], // أو file حسب نظامك
        ];
    }
}
