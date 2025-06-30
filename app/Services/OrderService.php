<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\User;
use App\Repositories\Eloquent\OrderRepository;
use App\Models\Product;
use App\Models\Area;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }



    public function confirmOrder($userId, $areaId, $addressId, $paymentMethod, $notes, array $products)
    {
        return DB::transaction(function () use ($userId, $areaId, $addressId, $paymentMethod, $notes, $products) {
            $area = Area::findOrFail($areaId);
            $user = User::findOrFail($userId);

            $productTotal = 0;
            $items = [];
            $discounts = [];
            $detailedProducts = [];

            foreach ($products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $price = $product->price;
                $quantity = $productData['quantity'];

                $itemTotalPrice = $price * $quantity;
                $productTotal += $itemTotalPrice;

                // check active discount today
                $activeDiscount = $product->activeDiscountToday();
                $discountValue = 0;
                if ($activeDiscount) {
                    $discountValue = ($price - $activeDiscount->new_price) * $quantity;
                    $discounts[] = [
                        'discount_id' => $activeDiscount->id,
                        'discount_fee' => $discountValue,
                    ];
                }

                $items[] = [
                    'product_id' => $product->id,
                    'store_id' => $product->store_id,
                    'quantity' => $quantity,
                    'price' => $itemTotalPrice,
                ];

                $detailedProducts[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'store_id' => $product->store_id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'unit_price_with_discount' => $activeDiscount ? $activeDiscount->new_price : $price,
                    'total_price' => $itemTotalPrice,
                    'total_price_with_discount' => $activeDiscount ? $activeDiscount->new_price * $quantity : $itemTotalPrice,
                ];
            }

            $totalDiscountFee = collect($discounts)->sum('discount_fee');
            $totalAfterDiscount = $productTotal - $totalDiscountFee;
            $deliveryFee = $totalAfterDiscount >= $area->free_delivery_from ? 0 : $area->delivery_fee;
            $totalPrice = $totalAfterDiscount + $deliveryFee;

            // تحديد الحالة حسب طريقة الدفع
            $status = 'pending';

            if ($paymentMethod === 'wallet') {
                if ($user->wallet_balance >= $totalPrice) {
                    // خصم الرصيد
                    $user->wallet_balance -= $totalPrice;
                    $user->save();
                    $status = 'preparing';
                }
            } elseif ($paymentMethod === 'cash') {
                $status = 'preparing';
            }

            // إنشاء الطلب
            $orderData = [
                'user_id' => $userId,
                'area_id' => $areaId,
                'address_id' => $addressId,
                'total_product_price' => $productTotal,
                'delivery_fee' => $deliveryFee,
                'total_price' => $totalPrice,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'status' => $status,
            ];

            $order = $this->orderRepo->create($orderData);
            $this->orderRepo->addItems($order->id, $items);

            if (!empty($discounts)) {
                foreach ($discounts as $discount) {
                    OrderDiscount::create([
                        'order_id' => $order->id,
                        'discount_id' => $discount['discount_id'],
                        'discount_fee' => $discount['discount_fee'],
                    ]);
                }
            }

            $orderDiscounts = OrderDiscount::where('order_id', $order->id)
                ->with('discount')
                ->get();

            $response = [];

            if ($paymentMethod === 'wallet' && $status === 'pending') {
                $response['message'] = 'الرصيد في المحفظة غير كافي. تم إنشاء الطلب بحالة pending.';
            }
            if ($paymentMethod === 'wallet' && $status === 'preparing') {
                $response['message'] = 'تم خصم المبلغ من المحفظة والطلب قيد التنفيذ';
            }

            $response += [
                'order_id' => $order->id,
                'status' => $order->status,
                'product_total' => $productTotal,
                'discount_fee' => $orderDiscounts->sum('discount_fee'),
                'total_after_discount' => $totalAfterDiscount,
                'delivery_fee' => $deliveryFee,
                'final_total' => $totalPrice,
                'items' => $detailedProducts,
            ];

            return $response;

        });
    }

    public function changePaymentMethod($orderId, $newPaymentMethod)
    {
        $order = $this->orderRepo->findById($orderId);
        $user  = $order->user;

        if ($order->status !== 'pending') {
            return ['message' => 'لا يمكن تغيير طريقة الدفع لأن الطلب ليس بحالة pending.'];
        }

        if ($newPaymentMethod === 'wallet') {
            if ($user->wallet_balance >= $order->total_price) {
                DB::transaction(function () use ($user, $order) {
                    $user->wallet_balance -= $order->total_price;
                    $user->save();

                    $order->payment_method = 'wallet';
                    $order->status = 'preparing';
                    $order->save();
                });

                return ['message' => 'تم خصم المبلغ من المحفظة وتحديث حالة الطلب إلى preparing.'];
            } else {
                return ['message' => 'الرصيد في المحفظة غير كافي.'];
            }
        }

        if ($newPaymentMethod === 'cash') {
            $order->payment_method = 'cash';
            $order->status = 'preparing';
            $order->save();

            return ['message' => 'تم تغيير طريقة الدفع إلى cash وتحديث حالة الطلب إلى preparing.'];
        }

        return ['message' => 'طريقة الدفع غير صحيحة.'];
    }



    public function getUserOrders($userId)
    {
        $orders = Order::where('user_id', $userId)
            ->with([
                'items.product',
                'items.store',
                'area',
                'orderDiscounts.discount'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $ordersData = $orders->map(function ($order) {
            $items = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'store_id' => $item->store->id,
                    'store_name' => $item->store->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                    'total_price' => $item->price,
                ];
            });

            $discounts = $order->orderDiscounts->map(function ($orderDiscount) {
                return [
                    'discount_id' => $orderDiscount->discount->id,
                    'discount_title' => $orderDiscount->discount->title,
                    'discount_fee' => (float) $orderDiscount->discount_fee,
                ];
            });

            $product_total = (float) $order->total_product_price;
            $discount_fee = $discounts->sum('discount_fee');
            $total_after_discount = $product_total - $discount_fee;
            $final_total = $total_after_discount + (float) $order->delivery_fee;

            return [
                'order_id' => $order->id,
                'status' => $order->status,
                'product_total' => $product_total,
                'discount_fee' => $discount_fee,
                'total_after_discount' => $total_after_discount,
                'delivery_fee' => $order->delivery_fee,
                'final_total' => $final_total,
                'payment_method' => $order->payment_method,
                'notes' => $order->notes,
                'date' => $order->date,
                'time' => $order->time,
                'area' => [
                    'id' => $order->area->id,
                    'name' => $order->area->name,
                ],
                'items' => $items,
                'discounts' => $discounts,
            ];
        });

        return $ordersData;
    }


    public function createOrder($userId, $areaId, $addressId, $paymentMethod, $notes, array $products)
    {
        return DB::transaction(function () use ($userId, $areaId, $addressId, $paymentMethod, $notes, $products) {
            $area = Area::findOrFail($areaId);

            $productTotal = 0;
            $items = [];
            $discounts = [];
            $detailedProducts = [];

            foreach ($products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $price = $product->price;
                $quantity = $productData['quantity'];

                $itemTotalPrice = $price * $quantity;
                $productTotal += $itemTotalPrice;

                // check active discount today
                $activeDiscount = $product->activeDiscountToday();
                $discountValue = 0;
                if ($activeDiscount) {
                    $discountValue = ($price - $activeDiscount->new_price) * $quantity;
                    $discounts[] = [
                        'discount_id' => $activeDiscount->id,
                        'discount_fee' => $discountValue,
                    ];
                }

                $items[] = [
                    'product_id' => $product->id,
                    'store_id' => $product->store_id,
                    'quantity' => $quantity,
                    'price' => $itemTotalPrice,  // السعر الإجمالي للمنتج مضروب بالكمية
                ];

                $detailedProducts[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'store_id' => $product->store_id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'unit_price_with_discount' => $activeDiscount->new_price,
                    'total_price' => $itemTotalPrice,
                    'total_price_with_discount' =>$activeDiscount->new_price*$quantity,

                ];
            }

            $totalDiscountFee = collect($discounts)->sum('discount_fee');

            $totalAfterDiscount = $productTotal - $totalDiscountFee;
            $deliveryFee = $totalAfterDiscount >= $area->free_delivery_from ? 0 : $area->delivery_fee;
            $totalPrice = $totalAfterDiscount + $deliveryFee;

            $orderData = [
                'user_id' => $userId,
                'area_id' => $areaId,
                'address_id' => $addressId,
                'total_product_price' => $productTotal,
                'delivery_fee' => $deliveryFee,
                'total_price' => $totalPrice,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ];

            // إنشاء الطلب
            $order = $this->orderRepo->create($orderData);

            // حفظ عناصر الطلب
            $this->orderRepo->addItems($order->id, $items);

            // حفظ الخصومات في جدول order_discounts
            if (!empty($discounts)) {
                foreach ($discounts as $discount) {
                    OrderDiscount::create([
                        'order_id' => $order->id,
                        'discount_id' => $discount['discount_id'],
                        'discount_fee' => $discount['discount_fee'],
                    ]);
                }
            }


            $orderDiscounts =OrderDiscount::where('order_id', $order->id)
                ->with('discount')
                ->get();

            return [
                'order_id' => $order->id,
                'status' => $order->status,
                'product_total' => $productTotal,
                'discount_fee' => $orderDiscounts->sum('discount_fee'),
                'total_after_discount' => $totalAfterDiscount,
                'delivery_fee' => $deliveryFee,
                'final_total' => $totalPrice,
                'items' => $detailedProducts,

            ];
        });
    }

}
