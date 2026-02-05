<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreAdminShowResource;
use App\Http\Resources\StoreResource;
use App\Models\User;
use App\Services\SubAdmin\StoreService;
use App\Services\SuperAdmin\AreaService;
use App\Services\SuperAdmin\SubAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    public function __construct(protected AreaService $service) {}
    public function store(Request $request)
    {

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'delivery_fee' => ['nullable','numeric','min:0'],
            'free_delivery_from' => ['nullable','numeric','min:0'],
        ], [
            'name.required' => 'اسم المنطقة مطلوب.',
            'name.max' => 'اسم المنطقة يجب ألا يتجاوز 255 حرفًا.',
            'delivery_fee.numeric' => 'رسوم التوصيل يجب أن تكون رقمًا.',
            'free_delivery_from.numeric' => 'المبلغ المجاني يجب أن يكون رقمًا.',
        ]);

        $area = $this->service->create($data);

        return response()->json([
            'message' => 'تم إنشاء المنطقة بنجاح.',
            'data' => $area
        ], 201);
    }
}



