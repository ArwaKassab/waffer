<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'temp_id' => ['required','string'], // مُعرّف الحزمة المؤقتة
            'phone'   => ['required','regex:/^09\d{8}$/'],
            'otp'     => ['required','string','min:4','max:10'],
        ];
    }
}
