<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cash,wallet',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $data = $this->orderService->createOrder(
            auth('sanctum')->id(),
            $validated['area_id'],
            $validated['address_id'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
            $validated['products']
        );

        return response()->json($data);
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cash,wallet',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        $data = $this->orderService->confirmOrder(
            auth('sanctum')->id(),
            $validated['area_id'],
            $validated['address_id'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
            $validated['products']
        );

        return response()->json($data);
    }


// تابع تغيير طريقة الدفع
    public function changePaymentMethod($orderId, Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,wallet',
        ]);

        $result = $this->orderService->changePaymentMethod($orderId, $validated['payment_method']);

        return response()->json($result);
    }

//    public function confirm($orderId)
//    {
//        $order = $this->orderService->confirmOrder($orderId, auth('sanctum')->id());
//        return response()->json(['message' => 'تم تأكيد الطلب', 'order' => $order]);
//    }

    public function myOrders()
    {
        $orders = $this->orderService->getUserOrders(auth('sanctum')->id());
        return response()->json($orders);
    }
}
