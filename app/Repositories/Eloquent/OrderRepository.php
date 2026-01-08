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



    public function findForUpdate(int $id): ?Order
    {
        return Order::lockForUpdate()->find($id);
    }

    public function setOrderStatusOnly(int $orderId, string $newStatus): bool
    {
        $order = Order::find($orderId);
        if (! $order) return false;

        $order->status = $newStatus;
        return (bool) $order->save();
    }

    public function setStatusWithItems(int $orderId, string $newStatus): bool
    {
        $order = Order::find($orderId);
        if (! $order) return false;

        $order->status = $newStatus;
        $saved = (bool) $order->save();

        if (! $saved) return false;

        OrderItem::query()
            ->where('order_id', $orderId)
            ->where('status', '!=', 'مرفوض')
            ->update([
                'status'     => $newStatus,
                'updated_at' => now(),
            ]);

        return true;
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




    public function PendingOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            // ✅ استبعاد الطلبات التي حالتها الكلية "انتظار"
            ->where('orders.status', '!=', 'انتظار')

            // ✅ شرط وجود عناصر لهذا المتجر حالتها "انتظار"
            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->where('status', 'انتظار');
            })

            ->select('orders.id', 'orders.status', 'orders.date', 'orders.time', 'orders.created_at')

            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->where('status', 'انتظار');
                }
            ])

            ->orderByDesc('orders.date')
            ->orderByDesc('orders.time')
            ->paginate($perPage);
    }



    /**
     * الطلبات التي فيها منتجات قيد التجهيز لهذا المتجر — مقسّمة صفحات.
     * هنا لا نهتم بحالة الطلب الكلية، فقط حالة الـ items الخاصة بالمتجر.
     */
    public function preparingOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()

            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->where('status', 'يجهز');
            })
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            // نحسب عدد المنتجات من هذا المتجر وحالتها "يجهز"
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->where('status', 'يجهز');
                }
            ])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    /**
     * الطلبات التي فيها منتجات المُنجزة لهذا المتجر — مقسّمة صفحات.
     * هنا لا نهتم بحالة الطلب الكلية، فقط حالة الـ items الخاصة بالمتجر.
     */
    public function DoneOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()

            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->where('status', 'حضر');
            })
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->where('status', 'حضر');
                }
            ])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate($perPage);
    }

    public function getRejectedOrdersForStore(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()

            ->whereHas('items', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->where('status', 'مرفوض');
            })
            ->select('orders.id', 'orders.date', 'orders.time', 'orders.created_at')
            ->withCount([
                'items as items_count' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                        ->where('status', 'مرفوض');
                }
            ])
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
                },
                'storeResponses' => function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                },
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
        return (bool) DB::transaction(function () use ($orderId, $newStatus) {
            $order = $this->find($orderId);
            if (!$order) {
                return false;
            }
            if ($order->status === $newStatus) {
                return true;
            }

            $order->status = $newStatus;

            return (bool) $order->save();
        });
    }



    //////////////////////////SUB ADMIN////////////////////////////
    /**
     * عدّاد طلبات "انتظار" لمنطقة معيّنة (بدون تقييد اليوم).
     */
    public function countTodayPendingByArea(int $areaId): int
    {
        return Order::query()
            ->where('area_id', $areaId)
            ->where('status', 'انتظار')
            ->count();
    }

    /**
     * إرجاع قائمة طلبات "انتظار" لمنطقة معيّنة (مع باجينيشن).
     * (نفس الاسم، لكن بدون شرط اليوم)
     */
    public function listTodayPendingByArea(int $areaId, int $perPage = 15)
    {
        return Order::query()
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
     * عدّاد طلبات "يجهز" لمنطقة معيّنة (بدون تقييد اليوم).
     */
    public function countTodayPreparingByArea(int $areaId): int
    {
        return Order::query()
            ->where('area_id', $areaId)
            ->where('status', 'يجهز')
            ->count();
    }

    /**
     * إرجاع قائمة طلبات "يجهز" لمنطقة معيّنة (مع باجينيشن).
     * (نفس الاسم، لكن بدون شرط اليوم)
     */
    public function listTodayPreparingByArea(int $areaId, int $perPage = 15)
    {
        return Order::query()
            ->where('area_id', $areaId)
            ->where('status', 'يجهز')
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
     * عدّاد طلبات "في الطريق" لمنطقة معيّنة.
     * (نفس الاسم، لكن بدون شرط اليوم)
     */
    public function countTodayOnWayByArea(int $areaId): int
    {
        return Order::query()
            ->where('area_id', $areaId)
            ->where('status', 'في الطريق')
            ->count();
    }
    /**
     * إرجاع قائمة طلبات "في الطريق" لمنطقة معيّنة (مع باجينيشن).
     * (نفس الاسم، لكن بدون شرط اليوم)
     */
    public function listTodayOnWayByArea(int $areaId, int $perPage = 15)
    {
        return Order::query()
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

                // ✅ هنا التعديل المهم
                'address' => function ($q) {
                    $q->withTrashed()->select([
                        'id',
                        'user_id',
                        'area_id',
                        'title',
                        'address_details',
                        'latitude',
                        'longitude',
                        'is_default',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ])->with(['area:id,name']);
                },
            ]);

        //////////////////////////////for store////////////////////////////////////
        /** @var \App\Models\Order|null $order */
        $order = $query->firstWhere('orders.id', $orderId);
        return $order;    }

    public function getStoreDoneOrdersBetweenDates(
        int    $storeId,
        string $fromDate,
        string $toDate
    ): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.store_id', $storeId)
            ->whereNull('orders.deleted_at')
            ->whereNull('order_items.deleted_at')
            ->where('order_items.status', 'حضر')
            ->whereBetween('orders.date', [$fromDate, $toDate])
            ->groupBy('order_items.order_id', 'orders.date', 'orders.time')
            ->selectRaw('
            order_items.order_id AS order_id,
            COUNT(DISTINCT order_items.product_id) AS items_count,
            orders.date,
            orders.time,
            SUM(order_items.total_price_after_discount) AS total_for_store
        ')
            ->orderBy('orders.date')
            ->orderBy('orders.time')
            ->get();

        $orders = $rows->map(function ($row) {
            return [
                'order_id'     => (int) $row->order_id,
                'date'         => $row->date,
                'time'         => $row->time,
                'items_count'  => (int) $row->items_count,
                'total_amount' => (float) $row->total_for_store,
            ];
        });

        return [
            'orders'       => $orders,
            'total_orders' => $orders->count(),
            'total_amount' => (float) $orders->sum('total_amount'),
        ];
    }



    public function getStoreRejectOrdersBetweenDates(
        int    $storeId,
        string $fromDate,
        string $toDate
    ): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.store_id', $storeId)
            ->whereNull('orders.deleted_at')
            ->whereNull('order_items.deleted_at')
            ->where('order_items.status', 'مرفوض')
            ->whereBetween('orders.date', [$fromDate, $toDate])
            ->groupBy('order_items.order_id', 'orders.date', 'orders.time')
            ->selectRaw('
            order_items.order_id AS order_id,
            COUNT(DISTINCT order_items.product_id) AS items_count,
            orders.date,
            orders.time,
            SUM(order_items.total_price_after_discount) AS total_for_store
        ')
            ->orderBy('orders.date')
            ->orderBy('orders.time')
            ->get();

        $orders = $rows->map(function ($row) {
            return [
                'order_id'     => (int) $row->order_id,
                'date'         => $row->date,
                'time'         => $row->time,
                'items_count'  => (int) $row->items_count,
            ];
        });

        return [
            'orders'       => $orders,
            'total_orders' => $orders->count(),
        ];
    }

    public function deleteCartItemsThatWereOrdered(
        int $userId,
        array $orderedProductIds
    ): void {
        DB::table('cart_items')
            ->whereIn('product_id', $orderedProductIds)
            ->whereIn('cart_id', function ($q) use ($userId) {
                $q->select('id')
                    ->from('carts')
                    ->where('user_id', $userId);
            })
            ->delete();
    }



}

