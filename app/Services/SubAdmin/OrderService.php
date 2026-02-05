<?php

namespace App\Services\SubAdmin;

use App\Events\NewOrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Repositories\Eloquent\OrderRepository;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private OrderRepository $orderRepo , WalletService $walletService)
    {
        $this->walletService = $walletService;
    }


    public const STATUS_PENDING  = 'انتظار';
    public const STATUS_ACCEPTED = 'يجهز';

    public const PAYMENT_WALLET = 'محفظة';
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
        event(new OrderStatusUpdated($order,$order->user_id));

        return [
            'success' => true,
            'message' => "تم تغيير حالة الطلب إلى {$newStatus} بنجاح.",
            'order'   => $this->orderRepo->find($orderId),
        ];
    }
    //قبول طلب
    public function acceptOrder(int $orderId): array
    {
        try {
            $order = DB::transaction(function () use ($orderId) {

                $order = $this->orderRepo->findForUpdate($orderId);

                if (! $order) {
                    return null;
                }
                if ($order->status !== self::STATUS_PENDING) {
                    throw ValidationException::withMessages([
                        'status' => "لا يمكن قبول الطلب لأن حالته الحالية هي: {$order->status}"
                    ]);
                }
                if (
                    $order->payment_method === self::PAYMENT_WALLET &&
                    $order->wallet_deducted_at === null
                ) {

                    $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                    $this->walletService->deductLocked($user, (float) $order->total_price);

                    $order->wallet_deducted_at = now();
                    $order->save();
                }

                $ok = $this->orderRepo->setOrderStatusOnly($order->id, self::STATUS_ACCEPTED);

                if (! $ok) {
                    throw ValidationException::withMessages([
                        'order' => 'تعذر تحديث حالة الطلب.'
                    ]);
                }


                return $order->fresh();
            });

            if (! $order) {
                return ['success' => false, 'message' => 'الطلب غير موجود.'];
            }

            event(new OrderStatusUpdated($order, $order->user_id));
            event(new NewOrderCreated($order));
            return [
                'success' => true,
                'message' => 'تم قبول الطلب بنجاح.',
                'order'   => $order,
            ];

        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first() ?? 'خطأ تحقق';
            return ['success' => false, 'message' => $first, 'errors' => $e->errors()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'حدث خطأ غير متوقع أثناء قبول الطلب.'];
        }
    }


    /**
     * يرجع عدد طلبات اليوم "انتظار" لمنطقة المستخدم المسجّل.
     */
    public function countPendingForLoggedArea($areaId): int
    {
        return $this->orderRepo->countPendingByArea((int) $areaId);
    }


    /**
     * يرجع قائمة طلبات اليوم "انتظار" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listPendingForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listPendingByArea((int) $areaId, $perPage);
    }

    /**
     * يرجع عدد طلبات اليوم "يجهز" لمنطقة المستخدم المسجّل.
     */
    public function countPreparingForLoggedArea($areaId): int
    {
        return $this->orderRepo->countPreparingByArea((int) $areaId);
    }


    /**
     * يرجع قائمة طلبات اليوم "يجهز" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listPreparingForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listPreparingByArea((int) $areaId, $perPage);
    }

    /**
     * يرجع عدد طلبات اليوم "في الطريق" لمنطقة المستخدم المسجّل.
     */
    public function countOnWayForLoggedArea($areaId): int
    {
        $user = Auth::user();
        return $this->orderRepo->countOnWayByArea((int) $areaId);
    }

    /**
     * يرجع قائمة طلبات اليوم "في الطريق" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listOnWayForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listOnWayByArea((int) $areaId, $perPage);
    }

    /**
     * يرجع عدد طلبات اليوم "مستلمة" لمنطقة المستخدم المسجّل.
     */
    public function countDoneForLoggedArea($areaId): int
    {
        $user = Auth::user();
        return $this->orderRepo->countDoneByArea((int) $areaId);
    }

    /**
     * يرجع قائمة طلبات اليوم "مستلمة" لمنطقة المستخدم المسجّل (مع باجينيشن).
     */
    public function listDoneForLoggedArea($areaId,int $perPage = 15)
    {

        return $this->orderRepo->listDoneByArea((int) $areaId, $perPage);
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

    public function listForAreaByStatus(int $areaId, string $status, int $perPage = 15)
    {
        return Order::query()
            ->where('area_id', $areaId)
            ->where('status', $status)
            ->with(['user:id,name,phone'])
            ->latest('id')
            ->select([
                'id','user_id','area_id','address_id',
                'total_product_price','discount_fee','totalAfterDiscount',
                'delivery_fee','total_price','date','time','status',
                'payment_method','notes','created_at'
            ])
            ->paginate($perPage);

    }

}
