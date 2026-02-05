<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Resources\SubAdminOrderDetailsResource;
use App\Services\SubAdmin\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }
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

    public function accept(Request $request, $orderId)
    {
        $response = $this->orderService->acceptOrder((int) $orderId);

        return response()->json(
            $response,
            $response['success'] ? 200 : 400
        );
    }


    /**
     * إرجاع عدد طلبات "انتظار" الخاصة بالمنطقة (بدون تقييد اليوم).
     */
    public function countTodayPending(Request $request)
    {
        $count = $this->orderService->countTodayPendingForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'status'  => 'انتظار',
            'count'   => $count,
        ]);
    }


    /**
     * إرجاع قائمة طلبات اليوم "انتظار" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listTodayPending(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);

        $orders = $this->orderService
            ->listTodayPendingForLoggedArea((int) $request->area_id, $perPage);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now()->toDateString(),
            'status'  => 'انتظار',
            'data'    => collect($orders->items())->map(function ($order) {
                return [
                    'order_id'    => $order->id,
                    'user' => [
                        'id'    => $order->user->id,
                        'name'  => $order->user->name,
                        'phone' => str_starts_with($order->user->phone, '00963')
                            ? '0' . substr($order->user->phone, 4)
                            : $order->user->phone,
                    ],
                    'total_price' => $order->total_price,
                    'payment_method' => $order->payment_method,
                    'date' => $order->date,
                    'time' => $order->time,
                ];
            }),
            'meta' => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }



    /**
     * إرجاع عدد طلبات "يجهز" الخاصة بالمنطقة (بدون تقييد اليوم).
     */
    public function countTodaypreparing(Request $request)
    {
        $count = $this->orderService->countTodayPreparingForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'status'  => 'يجهز',
            'count'   => $count,
        ]);
    }


    /**
     * إرجاع قائمة طلبات اليوم "يجهز" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listTodaypreparing(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);

        $orders = $this->orderService
            ->listTodayPendingForLoggedArea((int) $request->area_id, $perPage);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now()->toDateString(),
            'status'  => 'يجهز',
            'data'    => collect($orders->items())->map(function ($order) {
                return [
                    'order_id'    => $order->id,
                    'user' => [
                        'id'    => $order->user->id,
                        'name'  => $order->user->name,
                        'phone' => str_starts_with($order->user->phone, '00963')
                            ? '0' . substr($order->user->phone, 4)
                            : $order->user->phone,
                    ],
                    'total_price' => $order->total_price,
                    'payment_method' => $order->payment_method,
                    'date' => $order->date,
                    'time' => $order->time,
                ];
            }),
            'meta' => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }


    /**
     * إرجاع عدد طلبات اليوم "في الطريق" الخاصة بالمنطقة المسجّل دخول.
     */
    public function countTodayOnWay(Request $request)
    {
        $count = $this->orderService->countTodayOnWayForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now(config('app.timezone'))->toDateString(),
            'status'  => 'في الطريق',
            'count'   => $count,
        ]);
    }

    /**
     * إرجاع قائمة طلبات اليوم "في الطريق" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listTodayOnWay(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);

        $orders = $this->orderService
            ->listTodayPendingForLoggedArea((int) $request->area_id, $perPage);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now()->toDateString(),
            'status'  => 'في الطريق',
            'data'    => collect($orders->items())->map(function ($order) {
                return [
                    'order_id'    => $order->id,
                    'user' => [
                        'id'    => $order->user->id,
                        'name'  => $order->user->name,
                        'phone' => str_starts_with($order->user->phone, '00963')
                            ? '0' . substr($order->user->phone, 4)
                            : $order->user->phone,
                    ],
                    'total_price' => $order->total_price,
                    'payment_method' => $order->payment_method,
                    'date' => $order->date,
                    'time' => $order->time,
                ];
            }),
            'meta' => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }


    /**
     * إرجاع عدد طلبات اليوم "مستلم" الخاصة بالمنطقة المسجّل دخول.
     */
    public function countTodayDone(Request $request)
    {
        $count = $this->orderService->countTodayDoneForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now(config('app.timezone'))->toDateString(),
            'status'  => 'مستلم',
            'count'   => $count,
        ]);
    }

    /**
     * إرجاع قائمة طلبات اليوم "مستلم" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listTodayDone(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);

        $orders = $this->orderService
            ->listTodayPendingForLoggedArea((int) $request->area_id, $perPage);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now()->toDateString(),
            'status'  => 'مستلم',
            'data'    => collect($orders->items())->map(function ($order) {
                return [
                    'order_id'    => $order->id,
                    'user' => [
                        'id'    => $order->user->id,
                        'name'  => $order->user->name,
                        'phone' => str_starts_with($order->user->phone, '00963')
                            ? '0' . substr($order->user->phone, 4)
                            : $order->user->phone,
                    ],
                    'total_price' => $order->total_price,
                    'payment_method' => $order->payment_method,
                    'date' => $order->date,
                    'time' => $order->time,
                ];
            }),
            'meta' => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * عرض تفاصيل طلب للأدمن الفرعي  .
     */
    public function getOrderDetailsForSubAdmin(Request $request, int $orderId)
    {
        $order = $this->orderService->getOrderDetailsForSubAdmin($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود أو غير تابع لمنطقتك.',
            ], 404);
        }

        return (new SubAdminOrderDetailsResource($order))
            ->additional(['success' => true]);
    }
}
