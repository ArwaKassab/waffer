<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DiscountResource;
use App\Services\SubAdmin\DiscountService;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function __construct(private DiscountService $discountService) {}

    // إضافة عرض
    public function store(Request $request,int $product_id)
    {
        $data = $request->validate([
            'product_id' => $product_id,
            'new_price'  => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $discount = $this->discountService->createDiscount($data);

        return new DiscountResource($discount);
    }

    // تعديل عرض
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'new_price'  => 'numeric|min:0',
            'start_date' => 'date',
            'end_date'   => 'date|after_or_equal:start_date',
            'status'     => 'in:active,inactive',
        ]);

        $discount = $this->discountService->updateDiscount($id, $data);

        return $discount
            ? new DiscountResource($discount)
            : response()->json(['message' => 'العرض غير موجود'], 404);
    }

    // حذف عرض
    public function destroy(int $id)
    {
        $deleted = $this->discountService->deleteDiscount($id);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'تم حذف العرض' : 'العرض غير موجود',
        ]);
    }

    // عرض تفاصيل عرض واحد
    public function show(int $id)
    {
        $discount = $this->discountService->getDiscount($id);

        return $discount
            ? new DiscountResource($discount)
            : response()->json(['message' => 'العرض غير موجود'], 404);
    }

    // عرض كل العروض لمنتج معين
    public function index(Request $request, int $productId)
    {
        $discounts = $this->discountService->listDiscountsByProduct($productId);

        return DiscountResource::collection($discounts);
    }

    public function listByArea(Request $request)
    {

        $discounts = $this->discountService->listDiscountsForAdminArea($request->area_id);

        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }

    public function listByStore(Request $request, int $storeId)
    {
        $discounts = $this->discountService->listDiscountsForStore($storeId);

        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }
}
