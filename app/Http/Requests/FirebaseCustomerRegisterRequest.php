<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FirebaseCustomerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firebase_id_token'        => ['required', 'string'],

            'name'                     => ['required', 'string', 'max:255'],
            'phone'                    => ['nullable', 'string', 'max:30'],

            'password'                 => ['required', 'string', 'min:6'],
            'password_confirmation'    => ['required', 'same:password'],

            'area_id'                  => ['required', 'integer'],
            'latitude'                 => ['nullable'],
            'longitude'                => ['nullable'],
            'address_details'          => ['required', 'string', 'max:500'],
            'title'                    => ['required', 'string', 'max:100'],
        ];
    }
}
