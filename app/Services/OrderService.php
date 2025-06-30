<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\User;
use App\Models\Product;
use App\Models\Area;
use App\Repositories\Eloquent\OrderRepository;
use App\Services\WalletService;
use App\Events\OrderConfirmed;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    protected $orderRepo;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';

    public const PAYMENT_WALLET = 'wallet';
    public const PAYMENT_CASH = 'cash';

    public function __construct(OrderRepository $orderRepo, WalletService $walletService)
    {
        $this->orderRepo = $orderRepo;
        $this->walletService = $walletService;
    }

    protected function validatePaymentMethod(string $paymentMethod): void
    {
        if (!in_array($paymentMethod, [self::PAYMENT_WALLET, self::PAYMENT_CASH])) {
            throw ValidationException::withMessages([
                'payment_method' => __('order.invalid_payment_method')
            ]);
        }
    }

    protected function fetchUserAndArea(int $userId, int $areaId): array
    {
        $area = Area::findOrFail($areaId);
        $user = User::findOrFail($userId);

        return [$user, $area];
    }

    protected function fetchAndValidateProducts(array $products): \Illuminate\Support\Collection
    {
        $productIds = array_column($products, 'product_id');
        $productsData = Product::whereIn('id', $productIds)
            ->with(['discounts' => function ($q) {
                $q->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now());
            }])
            ->get()
            ->keyBy('id');

        if ($productsData->count() !== count($productIds)) {
            throw ValidationException::withMessages([
                'products' => __('order.invalid_products')
            ]);

        }

        return $productsData;
    }

    protected function handleWalletPayment(User $user, string $paymentMethod, float $amount): string
    {
        if ($paymentMethod === self::PAYMENT_WALLET) {
            if ($this->walletService->hasSufficientBalance($user, $amount)) {
                $this->walletService->deduct($user, $amount);
                return self::STATUS_PREPARING;
            }
            return self::STATUS_PENDING;
        }

        return self::STATUS_PREPARING;
    }


    public function confirmOrder(int $userId, int $areaId, int $addressId, string $paymentMethod, ?string $notes, array $products): array
    {
        $this->validatePaymentMethod($paymentMethod);

        [$order, $response] = DB::transaction(function () use ($userId, $areaId, $addressId, $paymentMethod, $notes, $products) {
            [$user, $area] = $this->fetchUserAndArea($userId, $areaId);
            $productsData = $this->fetchAndValidateProducts($products);
            $calculation = $this->calculateOrderDetails($products, $productsData, $area);
            $status = $this->handleWalletPayment($user, $paymentMethod, $calculation['final_total']);

            $order = $this->createOrder($userId, $areaId, $addressId, $paymentMethod, $notes, $calculation, $status);
            $this->storeOrderItemsAndDiscounts($order, $calculation);

            return [
                $order,
                [
                    'order_id' => $order->id,
                    'status' => $status,
                    'address_id' => $addressId,
                    'product_total' => $calculation['product_total'],
                    'discount_fee' => collect($calculation['discounts'])->sum('discount_fee'),
                    'total_after_discount' => $calculation['total_after_discount'],
                    'delivery_fee' => $calculation['delivery_fee'],
                    'final_total' => $calculation['final_total'],
                    'items' => $calculation['detailed_products'],
                    'message' => $this->getOrderMessage($paymentMethod, $status),
                ]
            ];
        });


        event(new OrderConfirmed($order));

        return $response;
    }

    protected function createOrder(int $userId, int $areaId, int $addressId, string $paymentMethod, ?string $notes, array $calculation, string $status): Order
    {
        return $this->orderRepo->create([
            'user_id' => $userId,
            'area_id' => $areaId,
            'address_id' => $addressId,
            'total_product_price' => $calculation['product_total'],
            'delivery_fee' => $calculation['delivery_fee'],
            'total_price' => $calculation['final_total'],
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'status' => $status,
        ]);
    }

    protected function storeOrderItemsAndDiscounts(Order $order, array $calculation): void
    {
        $this->orderRepo->addItems($order->id, $calculation['items']);

        foreach ($calculation['discounts'] as $discount) {
            OrderDiscount::create([
                'order_id' => $order->id,
                'discount_id' => $discount['discount_id'],
                'discount_fee' => $discount['discount_fee'],
            ]);
        }
    }


    protected function calculateOrderDetails(array $products, $productsData, Area $area): array
    {
        $productTotal = 0;
        $items = [];
        $discounts = [];
        $detailedProducts = [];

        foreach ($products as $productData) {
            $product = $productsData->get($productData['product_id']);
            $quantity = $productData['quantity'];

            if (!$product || $quantity <= 0) {
                throw ValidationException::withMessages([
                    'products' => __('order.invalid_quantity_or_product')
                ]);

            }

            $price = $product->price;
            $itemTotalPrice = $price * $quantity;
            $productTotal += $itemTotalPrice;

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
        $finalTotal = $totalAfterDiscount + $deliveryFee;

        return [
            'product_total' => $productTotal,
            'discounts' => $discounts,
            'total_after_discount' => $totalAfterDiscount,
            'delivery_fee' => $deliveryFee,
            'final_total' => $finalTotal,
            'items' => $items,
            'detailed_products' => $detailedProducts,
        ];
    }

    protected function getOrderMessage(string $paymentMethod, string $status): ?string
    {
        if ($paymentMethod === self::PAYMENT_WALLET) {
            return $status === self::STATUS_PREPARING
                ? __('order.wallet_deducted')
                : __('order.wallet_insufficient');
        }

        return null;
    }


    public function changePaymentMethod(int $orderId, string $newPaymentMethod): array
    {
        if (!in_array($newPaymentMethod, [self::PAYMENT_WALLET, self::PAYMENT_CASH])) {
            throw ValidationException::withMessages(['payment_method' => 'طريقة الدفع غير صحيحة']);
        }

        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw ValidationException::withMessages([
                'order' => __('order.order_not_found')
            ]);
        }

        if ($order->status !== self::STATUS_PENDING) {
            return ['message' => __('order.cannot_change_payment')];
        }

        $user = $order->user;

        return DB::transaction(function () use ($order, $user, $newPaymentMethod) {
            if ($newPaymentMethod === self::PAYMENT_WALLET) {
                if (!$this->walletService->hasSufficientBalance($user, $order->total_price)) {
                    return ['message' => __('order.wallet_not_enough')];
                }

                $this->walletService->deduct($user, $order->total_price);
                $order->payment_method = self::PAYMENT_WALLET;
            } elseif ($newPaymentMethod === self::PAYMENT_CASH) {
                $order->payment_method = self::PAYMENT_CASH;
            }

            $order->status = self::STATUS_PREPARING;
            $order->save();

            return [
                'message' => __('order.payment_method_updated', [
                    'method' => $this->getArabicPaymentMethod($newPaymentMethod)
                ])
            ];
        });
    }

    protected function getArabicPaymentMethod(string $method): string
    {
        return match ($method) {
            self::PAYMENT_WALLET => 'المحفظة',
            self::PAYMENT_CASH => 'نقدي',
            default => 'غير معروف',
        };
    }


    public function getUserOrders(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        $orders = Order::where('user_id', $userId)
            ->with([
                'items.product',
                'items.store',
                'area',
                'orderDiscounts.discount'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);


        $orders->getCollection()->transform(function ($order) {
            $items = $order->items->map(function ($item) {
                $product = optional($item->product);
                $store = optional($item->store);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $product->price,
                    'total_price' => $item->price,
                ];
            });

            $discounts = $order->orderDiscounts->map(function ($orderDiscount) {
                $discount = optional($orderDiscount->discount);

                return [
                    'discount_id' => $discount->id,
                    'discount_title' => $discount->title,
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
                'address_id' => $order->address_id,
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
                    'id' => optional($order->area)->id,
                    'name' => optional($order->area)->name,
                ],
                'items' => $items,
                'discounts' => $discounts,
            ];
        });

        return $orders;
    }
}
