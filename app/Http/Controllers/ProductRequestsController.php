<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequestStore;        // لـ تحديث منتج (FormRequest)
use App\Models\Product;
use App\Models\ProductRequest ; // تجنّب تضارب الاسم مع الـ FormRequest
use App\Models\User;
use App\Services\ProductRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRequestsController extends Controller
{
    public function __construct(
        private ProductRequestService $service
    ) {}

    public function updateRequest(ProductRequestStore $request, int $productId): JsonResponse
    {
        /** @var \App\Models\Product $product */

        $product = Product::findOrFail($productId);
        $product->load('store');
        $storeId = auth()->id();
        $req = $this->service->submitUpdateRequest(
            $product,
            $request->validated(),
            $storeId
        );

        return response()->json([
            'message'    => 'تم إنشاء طلب التعديل وبانتظار موافقة الأدمن.',
            'request_id' => $req->id,
        ], 201);
    }


    public function createRequest(ProductRequestStore $request): JsonResponse
    {
        $storeId = (int) auth()->id();
        $store   = User::findOrFail($storeId);

        $reqModel = $this->service->submitCreateRequest(
            $request->validated(),   // array
            $store->id               // int (store_id)

        );

        return response()->json([
            'message'    => 'تم إنشاء طلب إضافة المنتج وبانتظار موافقة الأدمن.',
            'request_id' => $reqModel->id,
        ], 201);
    }

    public function deleteRequest(int $productId): JsonResponse
    {
        $storeId = (int) auth()->id();
        $product = Product::where('id', $productId)
            ->where('store_id', $storeId)
            ->firstOrFail();

        $req = $this->service->submitDeleteRequest($product, $storeId);

        return response()->json([
            'message'    => 'تم إنشاء طلب الحذف وبانتظار موافقة الأدمن.',
            'request_id' => $req->id,
        ], 201);
    }

    // ---- admin ----

    public function approve(int $id, Request $request): JsonResponse
    {
        /** @var \App\Models\ProductRequest $req */
        $req = ProductRequest::findOrFail($id);
        $req->load('product'); // حمّل العلاقة بعدين
        $storeId = (int) auth()->id();

        $storeId = $req->action === 'create' ? $req->store_id : null;

        $product = $this->service->approve($req, $request->input('note'), $storeId);

        return response()->json([
            'message'    => 'تمت الموافقة وتطبيق الطلب.',
            'product_id' => $product->id,
        ]);
    }


    public function reject(int $id, Request $request): JsonResponse
    {
        /** @var ProductRequest $req */
        $req = ProductRequest::findOrFail($id);
        $req->load('product');

        $this->service->reject(
            $req,                           // \App\Models\ProductRequest
            (int) auth()->id(),             // int (admin_id)
            $request->input('note')         // ?string
        );

        return response()->json(['message' => 'تم رفض الطلب.']);
    }
}
