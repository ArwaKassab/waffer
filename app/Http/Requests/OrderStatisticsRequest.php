<?php


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatisticsRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
