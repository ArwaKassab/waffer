<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginStoreRequest extends FormRequest
{
    public function rules()
    {
        return [
            'phone'    => ['required', 'regex:/^0\d{9}$/'], // تحقق من رقم الهاتف
            'password' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'phone.required'    => 'رقم الهاتف مطلوب.',
            'phone.regex'       => 'يجب أن يبدأ رقم الهاتف بـ 0 ويتكون من 10 خانات.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }
}
