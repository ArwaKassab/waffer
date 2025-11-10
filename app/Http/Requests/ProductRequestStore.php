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
            'name'      => ['sometimes','string','max:255'],
            'price'     => ['sometimes','numeric','min:0'],
            'status'    => ['sometimes','in:available,not_available'],
            'quantity'  => ['sometimes','numeric','min:0'],
            'unit'      => ['sometimes','in:غرام,كيلوغرام,قطعة,لتر'],
            'image'     => ['sometimes','file','mimes:jpg,jpeg,png,webp','max:4096'],
            'details'   => ['sometimes','string','max:255'],
        ];
    }

}
