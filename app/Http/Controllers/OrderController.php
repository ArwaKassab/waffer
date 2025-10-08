<?php

namespace App\Http\Controllers;

use App\Http\Resources\StoreOrderResource;
use App\Http\Resources\StoreOrderSummaryResource;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /////////////////////for customer///////////////////////

    public function confirm(Request $request): JsonResponse
    {

        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:نقدي,محفظة',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $data = $this->orderService->confirmOrder(
            $request->user()->id,
            $validated['area_id'],
            $validated['address_id'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
            $validated['products']
        );

        return response()->json($data);
    }

    // تغيير طريقة الدفع
    public function changePaymentMethod(int $orderId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,wallet',
        ]);

        $data = $this->orderService->changePaymentMethod($orderId, $validated['payment_method']);

        return response()->json($data);
    }


    public function myOrders(Request $request)
    {
        $userId = $request->user()->id;
        $perPage = $request->get('per_page', 10);


        return $this->orderService->getUserOrders($userId, $perPage);
    }

    public function show(Request $request, int $orderId): JsonResponse
    {
        $userId = $request->user()->id;

        $order = $this->orderService->getUserOrderById($userId, $orderId);

        if (!$order) {
            return response()->json(['message' => 'الطلب غير موجود'], 404);
        }

        return response()->json($order);
    }

    public function orderStatus(Request $request, int $orderId): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $this->orderService->getOrderStatusForUser($userId, $orderId);
        if (!$data) {
            return response()->json(['message' => 'الطلب غير موجود'], 404);
        }

        return response()->json([
            'order_id'   => $data['order_id'],
            'status'     => $data['status'],
            'updated_at' => optional($data['updated_at'])->format('Y-m-d H:i'),
        ]);
    }


    ////////////////////////////for store/////////////////////
    public function pendingOrders(): JsonResponse
    {
        $storeId = auth()->id();
        $orders = $this->orderService->getPendingOrdersForStore($storeId);

        return response()->json([
            'orders' => StoreOrderSummaryResource::collection($orders)
        ]);
    }

    public function preparingOrders(): JsonResponse
    {
        $storeId = auth()->id();
        $orders = $this->orderService->getPreparingOrdersForStore($storeId);

        return response()->json([
            'orders' => StoreOrderSummaryResource::collection($orders)
        ]);
    }

    public function doneOrders(): JsonResponse
    {
        $storeId = auth()->id();
        $orders = $this->orderService->getDoneOrdersForStore($storeId);

        return response()->json([
            'orders' => StoreOrderSummaryResource::collection($orders)
        ]);
    }

    /**
     * طلبات هذا المتجر التي تم رفضها.
     */
    public function rejectedOrders(): JsonResponse
    {
        $storeId = auth()->id();
        $orders = $this->orderService->getRejectedOrdersForStore($storeId);

        return response()->json([
            'orders' => StoreOrderSummaryResource::collection($orders)
        ]);
    }

// عرض تفاصيل طلب معيّن خاص بالمتجر
    public function showStoreOrderDetails(int $orderId)
    {
        $storeId = auth()->id();
        $order = $this->orderService->getStoreOrderDetails($orderId, $storeId);

        if (!$order) {
            return response()->json(['message' => 'الطلب غير موجود أو لا يخص هذا المتجر'], 404);
        }

        return response()->json(StoreOrderResource::make($order));
    }


    public function acceptOrder(int $orderId)
    {
        $storeId = auth()->id();

        $result = $this->orderService->acceptStoreItems($orderId, $storeId);

        return response()->json([
            'message' => 'تم قبول الطلب من قبل المتجر',

        ]);
    }



    public function rejectOrder(Request $request, int $orderId)
    {
        $storeId = auth()->id();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $this->orderService->rejectOrderByStore($orderId, $storeId, $validated['reason'] ?? null);

        return response()->json(['message' => 'تم رفض الطلب من قبل المتجر']);
    }


    //////////////////////////////sub admin///////////////////////
    /**
     * تحديث حالة الطلب
     */
    public function changeStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $response = $this->orderService->updateOrderStatus($orderId, $validated['status']);

        $statusCode = $response['success'] ? 200 : 400;

        return response()->json($response, $statusCode);
    }


}

