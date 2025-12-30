<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginStoreRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', 'max:255'],
            'password'  => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'user_name.required'    => 'اسم المستخدم مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }
}
