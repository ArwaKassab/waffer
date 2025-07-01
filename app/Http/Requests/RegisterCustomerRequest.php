<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if ($this->has('phone') && preg_match('/^0\d{9}$/', $this->phone)) {
            $processedPhone = '00963' . substr($this->phone, 1);
            $this->merge([
                'phone' => $processedPhone,
            ]);
        }
    }

    public function rules()
    {
        return [
            'name'              => 'required|string|max:255',
            'phone'             => ['required', 'regex:/^00963\d{9}$/', 'unique:users,phone'],
            'password'          => 'required|string|min:6|confirmed',
            'area_id'           => 'required|exists:areas,id',
            'address_details'   => 'required|string',
            'latitude'          => 'required|numeric|between:-90,90',
            'longitude'         => 'required|numeric|between:-180,180',
        ];
    }

    public function messages()
    {
        return [
            'phone.required'    => 'رقم الهاتف مطلوب.',
            'phone.regex'       => 'يجب أن يبدأ رقم الهاتف بـ 0 ويتكون من 10 خانات.',
            'phone.unique'      => 'رقم الهاتف مسجل مسبقاً، الرجاء استخدام رقم آخر.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min'      => 'كلمة المرور يجب أن تكون على الأقل 6 أحرف.',
            'password.confirmed'=> 'تأكيد كلمة المرور غير مطابق.',
            'name.required'     => 'الاسم مطلوب.',
            'area_id.required'  => 'المنطقة مطلوبة.',
            'area_id.exists'    => 'المنطقة المختارة غير صحيحة.',
            'address_details.required' => 'تفاصيل العنوان مطلوبة.',
            'latitude.required' => 'خط العرض مطلوب.',
            'latitude.between'  => 'خط العرض يجب أن يكون بين -90 و 90.',
            'longitude.required'=> 'خط الطول مطلوب.',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180.',
        ];
    }
}
