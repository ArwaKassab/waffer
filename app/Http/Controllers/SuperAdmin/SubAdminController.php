<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreAdminShowResource;
use App\Http\Resources\StoreResource;
use App\Models\User;
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
        // التحقق من البيانات
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'user_name' => ['nullable','string','max:255','unique:users,user_name'],
            'area_id' => ['required','exists:areas,id'],
            'password' => ['required','string','min:8'],
        ], [
            'name.required' => 'اسم المستخدم مطلوب.',
            'user_name.unique' => 'اسم المستخدم موجود مسبقًا.',
            'area_id.required' => 'المنطقة مطلوبة.',
            'area_id.exists' => 'المنطقة غير موجودة.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
        ]);

        $subAdmin = $this->service->createSubAdmin($data);

        return response()->json([
            'message' => 'تم إنشاء Sub Admin بنجاح.',
            'data' => $subAdmin,
        ], 201);
    }
}



