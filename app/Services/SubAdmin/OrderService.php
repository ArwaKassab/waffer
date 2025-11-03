<?php

namespace App\Services\SubAdmin;

use App\Events\OrderStatusUpdated;
use App\Repositories\Eloquent\OrderRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderService
{
    public function __construct(private OrderRepository $orderRepo) {}

    /**
      * تحديث حالة الطلب
    */
    public function updateOrderStatus(int $orderId, string $newStatus): array
    {
        $allowed = $this->orderRepo->allowedStatuses();

        $validator = Validator::make(
            ['status' => $newStatus],
            ['status' => ['required', Rule::in($allowed)]]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'الحالة غير صالحة. المسموح: ' . implode('، ', $allowed),
            ];
        }
        $order = $this->orderRepo->find($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'الطلب غير موجود.',
            ];
        }

        $ok = $this->orderRepo->updateStatus($orderId, $newStatus);

        if (!$ok) {
            return [
                'success' => false,
                'message' => 'تعذر تحديث حالة الطلب.',
            ];
        }
        $order = $this->orderRepo->find($orderId);
        $customerUserId=$order->user_id;
        event(new OrderStatusUpdated($order,$customerUserId));

        return [
            'success' => true,
            'message' => "تم تغيير حالة الطلب إلى {$newStatus} بنجاح.",
            'order'   => $this->orderRepo->find($orderId),
        ];
    }


    /**
     * يرجع عدد طلبات اليوم "انتظار" لمنطقة المستخدم المسجّل.
     */
    public function countTodayPendingForLoggedArea($areaId): int
    {
        $user = Auth::user();
        return $this->orderRepo->countTodayPendingByArea((int) $areaId);
    }

    /**
     * يرجع قائمة طلبات اليوم "انتظار" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listTodayPendingForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listTodayPendingByArea((int) $areaId, $perPage);
    }

    /**
     * يرجع عدد طلبات اليوم "في الطريق" لمنطقة المستخدم المسجّل.
     */
    public function countTodayOnWayForLoggedArea($areaId): int
    {
        $user = Auth::user();
        return $this->orderRepo->countTodayOnWayByArea((int) $areaId);
    }

    /**
     * يرجع قائمة طلبات اليوم "في الطريق" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listTodayOnWayForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listTodayOnWayByArea((int) $areaId, $perPage);
    }

    /**
     * يرجع عدد طلبات اليوم "مستلمة" لمنطقة المستخدم المسجّل.
     */
    public function countTodayDoneForLoggedArea($areaId): int
    {
        $user = Auth::user();
        return $this->orderRepo->countTodayDoneByArea((int) $areaId);
    }

    /**
     * يرجع قائمة طلبات اليوم "مستلمة" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listTodayDoneForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listTodayDoneByArea((int) $areaId, $perPage);
    }

    /**
     * يجلب تفاصيل طلب للأدمن الفرعي اعتمادًا على منطقة المستخدم الحالي.
     * يعيد null إن لم يكن الطلب ضمن منطقة الأدمن الفرعي أو غير موجود.
     */
    public function getOrderDetailsForSubAdmin(int $orderId): ?\App\Models\Order
    {
        $user = Auth::user();
        $areaId = $user ? (int) data_get($user, 'area_id') : null;

        return $this->orderRepo->findDetailsForSubAdmin($orderId, $areaId);
    }
}
