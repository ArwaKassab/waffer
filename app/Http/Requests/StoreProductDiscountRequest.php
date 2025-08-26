<?php

// app/Http/Requests/StoreProductDiscountRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductDiscountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'new_price'  => ['required','numeric','min:0.01'],
            'start_date' => ['required','date'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
            'title'=> ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_price.min'      => 'السعر بعد الخصم يجب أن يكون أكبر من صفر.',
            'end_date.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون بعد أو يساوي تاريخ البداية.',
        ];
    }
}
