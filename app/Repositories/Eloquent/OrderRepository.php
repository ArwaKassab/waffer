<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\StoreOrderResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository
{
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
        return Order::with([
            'items.product',
            'items.store',
            'area',
            'orderDiscounts.discount'
        ])
            ->where('user_id', $userId)
            ->orderByDesc('date') // الأحدث بالتاريخ أولاً
            ->orderByDesc('time') // ثم الأحدث بالوقت
            ->paginate($perPage);
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



}
