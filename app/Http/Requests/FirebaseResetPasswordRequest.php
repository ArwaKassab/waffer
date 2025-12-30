<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FirebaseResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firebase_id_token'            => ['required', 'string'],
            'new_password'                 => ['required', 'string', 'min:6'],
            'new_password_confirmation'    => ['required', 'same:new_password'],
        ];
    }
}
