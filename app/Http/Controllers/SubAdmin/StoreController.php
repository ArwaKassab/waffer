<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Services\SubAdmin\StoreService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService
    ) {
    }

    /**
     * عرض المتاجر في نفس منطقة الأدمن الفرعي.
     */
    public function allArea(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);

        $storesPaginator = $this->storeService->getStoresForCurrentAdminArea($request,$perPage);

        if ($storesPaginator === null) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك أو لا يوجد منطقة مخصصة لك.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $storesPaginator->currentPage(),
                'data'         => $storesPaginator->items(), // العناصر فقط
            ],
        ]);
    }

    /**
     * حذف متجر (Soft Delete) تابع لمنطقة معيّنة.
     */
    public function destroy(Request $request, int $storeId)
    {
        $deleted = $this->storeService->deleteStoreForAdmin($request, $storeId);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'المتجر غير موجود أو لا يتبع لهذه المنطقة.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المتجر بنجاح (Soft Delete).',
        ]);
    }


}
