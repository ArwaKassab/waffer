<?php

// app/Http/Requests/ProductRequestStore.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequestStore extends FormRequest
{
    public function authorize(): bool {
        // تأكد أن المستخدم يملك المتجر/المنتج (Policy) أو على الأقل مصرح له
        return true;
    }
    public function rules(): array {
        return [
            'name'   => ['nullable','string','max:255'],
            'price'  => ['nullable','numeric','min:0'],
            'status' => ['nullable','in:available,not_available'],
            'quantity' => ['nullable','integer','min:0'],
            'unit'   => ['nullable','string','max:50'],
            'image'=>['nullable','string'],
        ];
    }
}
