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
            'name'      => ['required','string','max:255'],
            'price'     => ['required','numeric','min:0'],
            'status'    => ['required','in:available,not_available'],
            'quantity'  => ['required','numeric','min:0'],
            'unit'      => ['required','in:غرام,كيلوغرام,قطعة,لتر'],
            'image'     => ['nullable','file','mimes:jpg,jpeg,png,webp','max:4096'],
        ];
    }

}
