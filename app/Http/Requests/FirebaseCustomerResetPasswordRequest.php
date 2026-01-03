<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FirebaseCustomerResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firebase_id_token'        => ['required', 'string'],
            'password'                 => ['required', 'string', 'min:6'],
            'password_confirmation'    => ['required', 'same:password'],
        ];
    }
}
