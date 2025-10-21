<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\StoreOrderResponse;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderRepository
{

    protected array $statuses = ['انتظار', 'مقبول', 'يجهز', 'حضر', 'في الطريق', 'مستلم', 'مرفوض'];

    public function create(array $data)
    {
        return Order::create($data);
    }

    public function addItems($orderId, array $items)
    {
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'store_id' => $item['store_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price_after_discount' => $item['total_price_after_discount'],
                'total_price' => $item['total_price'],
                'unit_price_after_discount' => $item['unit_price_after_discount'],
                'discount_value' => $item['discount_value'],
            ]);
        }
    }
    public function addDiscounts($orderId, array $discounts)
    {
        foreach ($discounts as $discount) {
            OrderDiscount::create([
                'order_id' => $orderId,
                'discount_id' => $discount['discount_id'],
                'discount_fee' => $discount['discount_fee'],
            ]);
        }
    }

    public function findById($id)
    {
        return Order::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $order = $this->findById($id);
        if (!$order) {
            return null;
        }
        $order->update($data);
        return $order;
    }

    /**
     * إرجاع طلبات المستخدم مع باجينيشن.
     */
    public function getUserOrdersSortedByDate(int $userId, int $perPage = 10)
    {
        $orders = Order::with([
            'items.product',
            'items.store',
            'area',
            'orderDiscounts.discount'
        ])
            ->where('user_id', $userId)
            ->orderByDesc('date') // الأحدث بالتاريخ أولاً
            ->orderByDesc('time') // ثم الأحدث بالوقت
            ->paginate($perPage);

        return $orders;
    }




    /**
     * الطلبات بانتظار — مقسّمة صفحات.
     */
    public function PendingOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('status', 'انتظار')
            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                }
            ])
            // الأحدث بالتاريخ ثم الوقت
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    /**
     * الطلبات قيد التجهيز — مقسّمة صفحات.
     */
    public function preparingOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('status', 'يجهز')
            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                }
            ])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    /**
     * الطلبات المُنجزة — مقسّمة صفحات.
     */
    public function DoneOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('status', 'حضر')
            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                }
            ])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    public function getRejectedOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            // نجيب الطلبات اللي ردّ فيها المتجر بحالة "مرفوض"
            ->whereHas('storeOrderResponses', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->where('status', 'مرفوض');
            })
            // اختاري الحقول أولاً (عشان ما تطيحي items_count)
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            // نعدّ العناصر الخاصة بهذا المتجر من المصدر الأصلي (order_items)
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                }
            ])
            // الأحدث أولًا حسب التاريخ ثم الوقت
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);
    }


    // تفاصيل طلب واحد للمتجر -
    public function getStoreOrderDetails(int $orderId, int $storeId)
    {
        return Order::where('id', $orderId)
            ->whereHas('items', function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->with([
                'items' => function ($query) use ($storeId) {
                    $query->where('store_id', $storeId)->with('product');
                }
            ])
            ->first();
    }

    public function updateOrderItemsStatusForStore(int $orderId, int $storeId, string $status): void
    {
        OrderItem::where('order_id', $orderId)
            ->where('store_id', $storeId)
            ->update(['status' => $status]);
    }

    public function saveStoreResponse(int $orderId, int $storeId, string $status, ?string $reason = null)
    {
        $storeItems = OrderItem::where('order_id', $orderId)
            ->where('store_id', $storeId)
            ->get();

        $storeTotalInvoice = $storeItems->sum('total_price_after_discount');

        StoreOrderResponse::updateOrCreate(
            ['order_id' => $orderId, 'store_id' => $storeId],
            [
                'status' => $status,
                'reason' => $reason,
                'responded_at' => now(),
                'store_total_invoice' => $storeTotalInvoice,
            ]
        );
        return $storeTotalInvoice;
    }

    public function getOrderItemsStatuses(int $orderId): array
    {
        return OrderItem::where('order_id', $orderId)
            ->pluck('status')
            ->toArray();
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        Order::where('id', $orderId)->update(['status' => $status]);
    }

    public function getAcceptedItemsTotalPrice(int $orderId, int $storeId): float
    {
        return OrderItem::where('order_id', $orderId)
            ->where('store_id', $storeId)
            ->where('status', 'مقبول')
            ->sum('total_price_after_discount');
    }

    public function getStatusForUser(int $userId, int $orderId): ?Order
    {
        try {
            return Order::where('user_id', $userId)
                ->where('id', $orderId)
                ->select(['id','status','updated_at'])
                ->firstOrFail(); // مُtyped كـ Order
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }



    public function allowedStatuses(): array
    {
        return $this->statuses;
    }

    public function find(int $id): ?Order
    {
        return Order::find($id);
    }

    public function updateStatus(int $orderId, string $newStatus): bool
    {
        return DB::transaction(function () use ($orderId, $newStatus) {
            $order = $this->find($orderId);
            if (!$order) {
                return false;
            }
            $order->status = $newStatus;
            $saved = (bool) $order->save();

            if (!$saved) {
                return false;
            }
            OrderItem::query()
                ->where('order_id', $orderId)
                ->where('status', '!=', 'مرفوض')
                ->update([
                    'status'     => $newStatus,
                    'updated_at' => now(),
                ]);

            return true;
        });
    }


    //////////////////////////SUB ADMIN////////////////////////////
    /**
     * عدّاد طلبات اليوم بحالة "انتظار" لمنطقة معيّنة.
     */
    public function countTodayPendingByArea(int $areaId): int
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        return Order::query()
            ->whereDate('date', $today)
            ->where('area_id', $areaId)
            ->where('status', 'انتظار')
            ->count();
    }

    /**
     * إرجاع قائمة طلبات اليوم بحالة "انتظار" لمنطقة معيّنة (مع باجينيشن).
     */
    public function listTodayPendingByArea(int $areaId, int $perPage = 15)
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        return Order::query()
            ->whereDate('date', $today)
            ->where('area_id', $areaId)
            ->where('status', 'انتظار')
            ->latest('id')
            ->select([
                'id','user_id','area_id','address_id',
                'total_product_price','discount_fee','totalAfterDiscount',
                'delivery_fee','total_price','date','time','status','payment_method',
                'notes','created_at'
            ])
            ->paginate($perPage);
    }

    /**
     * عدّاد طلبات اليوم بحالة "في الطريق" لمنطقة معيّنة.
     */
    public function countTodayOnWayByArea(int $areaId): int
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        return Order::query()
            ->whereDate('date', $today)
            ->where('area_id', $areaId)
            ->where('status', 'في الطريق')
            ->count();
    }

    /**
     * إرجاع قائمة طلبات اليوم بحالة "في الطريق" لمنطقة معيّنة (مع باجينيشن).
     */
    public function listTodayOnWayByArea(int $areaId, int $perPage = 15)
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        return Order::query()
            ->whereDate('date', $today)
            ->where('area_id', $areaId)
            ->where('status', 'في الطريق')
            ->latest('id')
            ->select([
                'id','user_id','area_id','address_id',
                'total_product_price','discount_fee','totalAfterDiscount',
                'delivery_fee','total_price','date','time','status','payment_method',
                'notes','created_at'
            ])
            ->paginate($perPage);
    }

    /**
     * عدّاد طلبات اليوم بحالة "مستلم" لمنطقة معيّنة.
     */
    public function countTodayDoneByArea(int $areaId): int
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        return Order::query()
            ->whereDate('date', $today)
            ->where('area_id', $areaId)
            ->where('status', 'مستلم')
            ->count();
    }

    /**
     * إرجاع قائمة طلبات اليوم بحالة "مستلمة" لمنطقة معيّنة (مع باجينيشن).
     */
    public function listTodayDoneByArea(int $areaId, int $perPage = 15)
    {
        $today = Carbon::today(config('app.timezone'))->toDateString();

        return Order::query()
            ->whereDate('date', $today)
            ->where('area_id', $areaId)
            ->where('status', 'مستلم')
            ->latest('id')
            ->select([
                'id','user_id','area_id','address_id',
                'total_product_price','discount_fee','totalAfterDiscount',
                'delivery_fee','total_price','date','time','status','payment_method',
                'notes','created_at'
            ])
            ->paginate($perPage);
    }

    /**
     * إحضار تفاصيل طلب للأدمن الفرعي مع تقييد المنطقة (إن وُجدت),
     *
     * @param  int      $orderId
     * @param  int|null $areaId  لو محدد: نحصر الطلب بهذه المنطقة
     * @return \App\Models\Order|null
     */
    public function findDetailsForSubAdmin(int $orderId, ?int $areaId = null): ?Order
    {
        $query = Order::query()
            ->with([
                'items' => function ($q) {
                    $q->select([
                        'id','order_id','product_id','store_id',
                        'quantity','status',
                        'unit_price','unit_price_after_discount',
                        'discount_value','total_price','total_price_after_discount',
                        'created_at','updated_at',
                    ])->with([
                        'product' => fn($p) => $p->withTrashed()->select('id','name'),
                        'store:id,name',
                    ])->orderBy('id');
                },
                'user:id,name,phone',
                'area:id,name',
                'address:id',
            ]);

        if ($areaId) {
            $query->where('area_id', $areaId);
        }
        /** @var \App\Models\Order|null $order */
        $order = $query->firstWhere('orders.id', $orderId);
        return $order;    }




}

