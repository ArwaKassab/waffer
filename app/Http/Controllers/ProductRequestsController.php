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

    public function updateRequest(ProductRequestStore $request, int $productId): JsonResponse
    {
        /** @var Product $product */

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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('productس', 'public');

        }

        $reqModel = $this->service->submitCreateRequest($data, $storeId);
        $imageUrl = Storage::url($data['image']);
        return response()->json([
            'message'    => 'تم إنشاء طلب إضافة المنتج وبانتظار موافقة الأدمن.',
            'request_id' => $reqModel->id,
            'image_url'  => $imageUrl,  // تم إضافة الرابط الصحيح هنا
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

    public function updatePending(ProductRequestUpdatePending $request, int $requestId): JsonResponse
    {
        $storeId = (int) auth()->id();

        $data = $request->validated();

        if ($request->has('status')) {
            $data['status_value'] = $request->input('status');
            unset($data['status']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        $imageUrl = Storage::url($data['image']);

        \Log::info('update-pending incoming', $data);
        $req = $this->service->editPendingRequest($requestId, $storeId, $data);

        return response()->json([
            'message'    => 'تم تعديل الطلب وهو ما يزال بانتظار موافقة الأدمن.',
            'request_id' => $req->id,
            'action'     => $req->action,
            'status'     => $req->status,
            'data'       => [
                'name'     => $req->name,
                'price'    => $req->price,
                'status'   => $req->status_value,
                'quantity' => $req->quantity,
                'unit'     => $req->unit,
                'image'    => $imageUrl,
            ],
        ]);
    }



    public function getPendingRequests(): JsonResponse
    {
        $storeId = (int) auth()->id();

        $requests = $this->service->getPendingRequests($storeId);

        return response()->json([
            'message'   => 'الطلبات المعلّقة',
            'requests'  => $requests,
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
