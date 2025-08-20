<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => ['required','string','max:255'],
            'phone'           => ['required','string','min:6','max:20'],
            'password'        => ['required','string','min:6','max:255'],
            'area_id'         => ['required','integer','exists:areas,id'],
            'address_details' => ['required','string','max:500'],
            'latitude'        => ['nullable','numeric'],
            'longitude'       => ['nullable','numeric'],
            'title'           => ['required','string','max:255'],
        ];
    }
}
