<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;

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
                'price' => $item['price'],  // لازم السعر الكلي يكون هنا
            ]);
        }
    }




    public function getOrdersByUser($userId)
    {
        return Order::with(['area', 'items.product', 'orderDiscounts'])
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
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
            return null; // أو ترفع استثناء
        }
        $order->update($data);
        return $order;
    }

}
