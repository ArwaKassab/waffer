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



    /**
     * إرجاع عدد طلبات اليوم "انتظار" الخاصة بالمنطقة المسجّل دخول.
     */
    public function countTodayPending(Request $request)
    {
        $count = $this->orderService->countTodayPendingForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now(config('app.timezone'))->toDateString(),
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
        $orders  = $this->orderService->listTodayPendingForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'انتظار',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return response()->json($orders);
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
        $orders  = $this->orderService->listTodayOnWayForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'في الطريق',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return response()->json($orders);
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
        $orders  = $this->orderService->listTodayDoneForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'مستلم',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return response()->json($orders);
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
