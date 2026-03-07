<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Services\SubAdmin\AreaHomeOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaHomeOrderController extends Controller
{
    public function __construct(protected AreaHomeOrderService $service) {}

    // POST /super-admin/areas/{areaId}/home-order/categories
    public function setCategoriesOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_ids' => ['required','array','min:1'],
            'category_ids.*' => ['integer'],
        ]);

        $this->service->setCategoryOrder($request->area_id, $data['category_ids']);

        return response()->json(['message' => 'تم حفظ ترتيب التصنيفات.']);
    }

    // POST /super-admin/areas/{areaId}/home-order/stores
    public function setStoresOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_ids' => ['required','array','min:1'],
            'store_ids.*' => ['integer'],
        ]);

        $this->service->setStoreOrder($request->area_id, $data['store_ids']);

        return response()->json(['message' => 'تم حفظ ترتيب المتاجر.']);
    }

    // PATCH /super-admin/areas/{areaId}/home-order/categories/toggle
    public function toggleCategory(Request $request,int $category_id): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['required','boolean'],
        ]);

        $this->service->toggleCategory($request->area_id, $category_id, $data['is_active']);

        return response()->json(['message' => 'تم تحديث حالة ظهور التصنيف.']);
    }

    // PATCH /super-admin/areas/{areaId}/home-order/stores/toggle
    public function toggleStore(Request $request ,int $store_id): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['required','boolean'],
        ]);

        $this->service->toggleStore($request->area_id, $store_id, $data['is_active']);

        return response()->json(['message' => 'تم تحديث حالة ظهور المتجر.']);
    }
}
