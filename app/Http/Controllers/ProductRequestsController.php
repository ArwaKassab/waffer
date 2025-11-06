<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequestStore;
use App\Http\Requests\ProductRequestUpdatePending;
use App\Models\Product;
use App\Models\ProductRequest ;
use App\Models\User;
use App\Services\ProductRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductRequestsController extends Controller
{
    public function __construct(
        private ProductRequestService $service
    ) {}

    /**
     * يبني بيانات المنتج النهائية: ما تم تعديله في الطلب + باقي الحقول من الـ Product نفسه.
     */
    private function buildMergedProductData(ProductRequest $req): array
    {
        // نتأكد إن المنتج محمّل
        $req->loadMissing('product');

        /** @var Product|null $product */
        $product = $req->product;

        // لو الحقل موجود في الطلب نستخدمه، غير هيك نرجع لقيمة المنتج
        $name     = $req->name         ?? ($product?->name   ?? null);
        $price    = $req->price        ?? ($product?->price  ?? null);
        $status   = $req->status_value ?? ($product?->status ?? null);
        $quantity = $req->quantity     ?? ($product?->quantity ?? null);
        $unit     = $req->unit         ?? ($product?->unit   ?? null);

        // الصورة: لو في صورة بالطلب نستخدمها، غير هيك نستخدم صورة المنتج
        if ($req->image) {
            $imageUrl = $req->image_url;
        } elseif ($product) {
            $imageUrl = $product->image_url;
        } else {
            $imageUrl = null;
        }

        return [
            'product_id' => $product?->id ?? $req->product_id,
            'store_id'   => $req->store_id,
            'name'       => $name,
            'price'      => $price,
            'status'     => $status,
            'quantity'   => $quantity,
            'unit'       => $unit,
            'image'      => $imageUrl,
        ];
    }

    public function updateRequest(ProductRequestStore $request, int $productId): JsonResponse
    {
        /** @var Product $product */
        $product = Product::findOrFail($productId);
        $product->load('store');

        $storeId = (int) auth()->id();
        $data    = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('product-requests', 'public');
        } else {
            unset($data['image']);
        }

        /** @var ProductRequest $req */
        $req = $this->service->submitUpdateRequest($product, $data, $storeId);

        // ندمج بيانات الطلب مع المنتج
        $merged = $this->buildMergedProductData($req);

        return response()->json([
            'message'    => 'تم إنشاء طلب التعديل وبانتظار موافقة الأدمن.',
            'request_id' => $req->id,
            'action'     => $req->action,
            'status'     => $req->status,
            'data'       => $merged,
        ], 201);
    }




    public function createRequest(ProductRequestStore $request): JsonResponse
    {
        $storeId = (int) auth()->id();
        $data    = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('product-requests', 'public');
        }

        /** @var ProductRequest $reqModel */
        $reqModel = $this->service->submitCreateRequest($data, $storeId)->refresh();

        $merged = $this->buildMergedProductData($reqModel);

        return response()->json([
            'message'    => 'تم إنشاء طلب إضافة المنتج وبانتظار موافقة الأدمن.',
            'request_id' => $reqModel->id,
            'action'     => $reqModel->action,
            'status'     => $reqModel->status,
            'data'       => $merged,
        ], 201);
    }




    public function deleteRequest(int $productId): JsonResponse
    {
        $storeId = (int) auth()->id();

        $product = Product::where('id', $productId)
            ->where('store_id', $storeId)
            ->firstOrFail();

        /** @var ProductRequest $req */
        $req = $this->service->submitDeleteRequest($product, $storeId);

        $merged = $this->buildMergedProductData($req);

        return response()->json([
            'message'    => 'تم إنشاء طلب الحذف وبانتظار موافقة الأدمن.',
            'request_id' => $req->id,
            'action'     => $req->action,
            'status'     => $req->status,
            'data'       => $merged,
        ], 201);
    }



    public function updatePending(ProductRequestUpdatePending $request, int $requestId): JsonResponse
    {
        $storeId = (int) auth()->id();

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }

        /** @var ProductRequest $req */
        $req = $this->service->editPendingRequest($requestId, $storeId, $data);

        $merged = $this->buildMergedProductData($req);

        return response()->json([
            'message'    => 'تم تعديل الطلب وهو ما يزال بانتظار موافقة الأدمن.',
            'request_id' => $req->id,
            'action'     => $req->action,
            'status'     => $req->status,
            'data'       => $merged,
        ]);
    }



    public function getPendingRequests(): JsonResponse
    {
        $storeId = (int) auth()->id();

        $requests = $this->service->getPendingRequests($storeId);

        $formatted = $requests->map(function (ProductRequest $req) {
            return [
                'id'      => $req->id,
                'action'  => $req->action,
                'status'  => $req->status,
                'data'    => $this->buildMergedProductData($req),
                'created_at' => $req->created_at,
            ];
        });

        return response()->json([
            'message'   => 'الطلبات المعلّقة',
            'requests'  => $formatted,
        ], 200);
    }


    // ---- admin ----

    public function approve(int $id, Request $request): JsonResponse
    {
        /** @var ProductRequest $req */
        $req = ProductRequest::findOrFail($id);
        $req->load('product');
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
            $req,
            (int) auth()->id(),
            $request->input('note')
        );

        return response()->json(['message' => 'تم رفض الطلب.']);
    }

    public function deleteCreateRequest(int $id): JsonResponse
    {
        $storeId = (int) auth()->id();

        $this->service->cancelCreateRequest($id, $storeId);

        return response()->json([
            'message' => 'تم حذف طلب الإضافة المعلّق بنجاح.',
            'request_id' => $id,
        ], 200);
    }
}
