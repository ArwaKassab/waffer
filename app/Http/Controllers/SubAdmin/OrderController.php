<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Resources\OrderListResource;
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
    public function countPending(Request $request)
    {
        $count = $this->orderService->countPendingForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'status'  => 'انتظار',
            'count'   => $count,
        ]);
    }


    /**
     * إرجاع قائمة طلبات اليوم "انتظار" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listPending(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);
        $orders  = $this->orderService->listPendingForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'انتظار',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return OrderListResource::collection($orders);    }


    /**
     * إرجاع عدد طلبات "يجهز" الخاصة بالمنطقة (بدون تقييد اليوم).
     */
    public function countpreparing(Request $request)
    {
        $count = $this->orderService->countPreparingForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'status'  => 'يجهز',
            'count'   => $count,
        ]);
    }


    /**
     * إرجاع قائمة طلبات اليوم "يجهز" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listpreparing(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);
        $orders  = $this->orderService->listPreparingForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'يجهز',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return OrderListResource::collection($orders);
    }


    /**
     * إرجاع عدد طلبات  "في الطريق" الخاصة بالمنطقة المسجّل دخول.
     */
    public function countOnWay(Request $request)
    {
        $count = $this->orderService->countOnWayForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now(config('app.timezone'))->toDateString(),
            'status'  => 'في الطريق',
            'count'   => $count,
        ]);
    }

    /**
     * إرجاع قائمة طلبات  "في الطريق" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listOnWay(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);
        $orders  = $this->orderService->listOnWayForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'في الطريق',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return OrderListResource::collection($orders);
    }


    /**
     * إرجاع عدد طلبات  "مستلم" الخاصة بالمنطقة المسجّل دخول.
     */
    public function countTodayDone(Request $request)
    {
        $count = $this->orderService->countDoneForLoggedArea($request->area_id);

        return response()->json([
            'area_id' => (int) $request->area_id,
            'date'    => now(config('app.timezone'))->toDateString(),
            'status'  => 'مستلم',
            'count'   => $count,
        ]);
    }

    /**
     * إرجاع قائمة طلبات  "مستلم" (مع باجينيشن) للمنطقة المسجّل دخول.
     */
    public function listDone(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$request->area_id) {
            return response()->json(['message' => 'لا يوجد منطقة للمستخدم الحالي'], 400);
        }

        $perPage = (int) $request->query('per_page', 15);
        $orders  = $this->orderService->listDoneForLoggedArea($request->area_id,$perPage);

        if (is_a($orders, Collection::class)) {
            return response()->json([
                'area_id' => (int) $request->area_id,
                'date'    => now(config('app.timezone'))->toDateString(),
                'status'  => 'مستلم',
                'data'    => [],
                'meta'    => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1],
            ]);
        }

        return OrderListResource::collection($orders);
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
