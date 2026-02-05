<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreAdminShowResource;
use App\Http\Resources\StoreResource;
use App\Models\User;
use App\Repositories\Eloquent\AreaRepository;
use App\Services\AreaService;
use App\Services\SubAdmin\StoreService;
use App\Services\SuperAdmin\SubAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubAdminController extends Controller
{
    public function __construct(protected SubAdminService $service) {}
    public function addSubAdmin(Request $request)
    {
        // التحقق من البيانات الأولية
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'user_name' => ['nullable','string','max:255','unique:users,user_name'],
            'area_id' => ['required','exists:areas,id'],
            'password' => ['required','string','min:8'],
            'phone' => ['required','string','size:10'], // 10 أرقام
        ], [
            'name.required' => 'اسم المستخدم مطلوب.',
            'user_name.unique' => 'اسم المستخدم موجود مسبقًا.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.size' => 'رقم الهاتف يجب أن يكون 10 أرقام.',
            'area_id.required' => 'المنطقة مطلوبة.',
            'area_id.exists' => 'المنطقة غير موجودة.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
        ]);

        // معالجة الرقم ليصبح بصيغة 00963...
        if (str_starts_with($data['phone'], '0')) {
            $data['phone'] = '00963' . substr($data['phone'], 1);
        }

        // التحقق من عدم تكرار الرقم لنفس النوع
        if (\App\Models\User::where('phone', $data['phone'])->where('type', 'sub_admin')->exists()) {
            return response()->json([
                'message' => 'هذا الرقم مستخدم بالفعل لمستخدم من نفس النوع.'
            ], 422);
        }

        // إنشاء Sub Admin
        $subAdmin = $this->service->createSubAdmin($data);

        return response()->json([
            'message' => 'تم إنشاء Sub Admin بنجاح.',
        ], 201);
    }

}



