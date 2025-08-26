<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendRegisterOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'temp_id' => ['required','string'],     // ممكن uuid
            'phone'   => ['required','regex:/^09\d{8}$/'], // 09xxxxxxxx
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكوّن من 10 أرقام.',
        ];
    }
}
