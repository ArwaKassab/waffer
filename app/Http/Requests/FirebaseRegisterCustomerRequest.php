<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FirebaseRegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firebase_id_token' => ['required', 'string'],
            'name'              => ['required', 'string', 'max:255'],
            'password'          => ['nullable', 'string', 'min:6'],
            'area_id'           => ['required', 'integer'],
            'address_details'   => ['required', 'string', 'max:500'],
            'title'             => ['required', 'string', 'max:100'],
            'latitude'          => ['nullable', 'numeric'],
            'longitude'         => ['nullable', 'numeric'],
        ];
    }
}
