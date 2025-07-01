<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    // تأكيد الطلب
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cash,wallet',
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


}
