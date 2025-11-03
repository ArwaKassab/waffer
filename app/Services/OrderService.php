<?php

namespace App\Services;

use App\Http\Resources\StoreOrderResource;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\StoreOrderResponse;
use App\Models\User;
use App\Models\Product;
use App\Models\Area;
use App\Repositories\Eloquent\OrderRepository;
use App\Services\WalletService;
use App\Events\OrderConfirmed;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ConfirmedOrderResource;

class OrderService
{
    protected $orderRepo;
    protected $walletService;

    public const STATUS_PENDING = 'انتظار';
    public const STATUS_PREPARING = 'مقبول';

    public const PAYMENT_WALLET = 'محفظة';
    public const PAYMENT_CASH = 'نقدي';

    public function __construct(OrderRepository $orderRepo, WalletService $walletService)
    {
        $this->orderRepo = $orderRepo;
        $this->walletService = $walletService;
    }

    /**
     * تأكيد الطلب: يتضمن التحقق من وسيلة الدفع، وحساب الأسعار، وإنشاء الطلب.
     */
    public function confirmOrder(int $userId, int $areaId, int $addressId, string $paymentMethod, ?string $notes, array $products): array
    {
        // التحقق من أن وسيلة الدفع صحيحة (محفظة أو كاش فقط)
        $this->validatePaymentMethod($paymentMethod);

        [$order, $response] = DB::transaction(function () use ($userId, $areaId, $addressId, $paymentMethod, $notes, $products) {

            // جلب بيانات المستخدم والمنطقة للتأكد من وجودهما واستخدامها في الحسابات التالية
            [$user, $area] = $this->fetchUserAndArea($userId, $areaId);

            // التحقق من صحة المنتجات المطلوبة وتحميل خصوماتها النشطة، وإقفال الصفوف لمنع تعارضات أثناء الطلب
            $productsData = $this->fetchAndValidateProducts($products);

            // حساب السعر الكلي للمنتجات، الخصومات، رسوم التوصيل، والسعر النهائي
            $calculation = $this->calculateOrderDetails($products, $productsData, $area);

            // التحقق من توفر الرصيد في المحفظة إذا كانت وسيلة الدفع محفظة، ورمي خطأ إذا لم يكن كافيًا
            $status = $this->handleWalletPayment($user, $paymentMethod, $calculation['final_total']);

            // إنشاء الطلب الجديد في قاعدة البيانات بناءً على الحسابات السابقة والمعلومات المدخلة
            $order = $this->createOrder($userId, $areaId, $addressId, $paymentMethod, $notes, $calculation, $status);

            // حفظ المنتجات المرتبطة بالطلب وكذلك الخصومات التي تم تطبيقها
            $this->storeOrderItemsAndDiscounts($order, $calculation);

            //  تجهيز بيانات الطلب لإرجاعها في الاستجابة
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
                    //  توليد رسالة توضح حالة الرصيد (هل كان كافيًا أم لا) حسب وسيلة الدفع
                    'message' => $this->getOrderMessage($paymentMethod, $calculation['final_total'], $calculation['final_total']),
                ]
            ];
        });

//        // إطلاق حدث بعد تأكيد الطلب، يمكن أن يستخدم لإرسال إشعار أو تنفيذ إجراءات أخرى
//        event(new OrderConfirmed($order));
//        // بعد ما تحفظي الطلب وتعرفي لأي متجر رايح
//        event(new \App\Events\NewOrderCreated($order, $storeUserId));

        // تحويل استجابة الطلب إلى تنسيق API مناسب وإرجاعه
        return (new ConfirmedOrderResource($response))->resolve();
    }


    /**
     * تغيير وسيلة الدفع لطلب قائم.
     */
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
                $order->payment_method = self::PAYMENT_WALLET;
            } elseif ($newPaymentMethod === self::PAYMENT_CASH) {
                $order->payment_method = self::PAYMENT_CASH;
            }

            $order->save();

            return [
                'message' => __('order.payment_method_updated', [
                    'method' => $this->getArabicPaymentMethod($newPaymentMethod)
                ])
            ];
        });
    }

    /**
     * تحقق من صحة وسيلة الدفع (محفظة/كاش).
     */
    protected function validatePaymentMethod(string $paymentMethod): void
    {
        if (!in_array($paymentMethod, [self::PAYMENT_WALLET, self::PAYMENT_CASH])) {
            throw ValidationException::withMessages([
                'payment_method' => __('order.invalid_payment_method')
            ]);
        }
    }

    /**
     * استعراض بيانات المستخدم والمنطقة المطلوبة.
     */
    protected function fetchUserAndArea(int $userId, int $areaId): array
    {
        $area = Area::findOrFail($areaId);
        $user = User::findOrFail($userId);

        return [$user, $area];
    }

    /**
     * تحميل وتحقق من صحة المنتجات وتطبيق الخصم النشط.
     */
    protected function fetchAndValidateProducts(array $products): \Illuminate\Support\Collection
    {
        $productIds = array_column($products, 'product_id');
        $productsData = Product::whereIn('id', $productIds)
            ->lockForUpdate()
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

    /**
     * حساب السعر والخصوم ورسوم التوصيل.
     */
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
            $itemDiscount = $activeDiscount
                ? ($price - $activeDiscount->new_price) * $quantity
                : 0;
            $items[] = [
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'quantity' => $quantity,
                'unit_price_after_discount'=>$activeDiscount ? $activeDiscount->new_price : $price,
                'unit_price' =>$price,
                'total_price_after_discount'=>$activeDiscount ? $activeDiscount->new_price * $quantity : $product->price * $quantity,
                'total_price' => $itemTotalPrice,
                'discount_value' => $itemDiscount,
                'image' => $product->image_url,

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
                'discount_value' => $itemDiscount,
                'image' => $product->image_url,
            ];
        }

        $totalDiscountFee = collect($discounts)->sum('discount_fee');
        $totalAfterDiscount = $productTotal - $totalDiscountFee;
        $deliveryFee = $totalAfterDiscount >= $area->free_delivery_from ? 0 : $area->delivery_fee;
        $finalTotal = $totalAfterDiscount + $deliveryFee;

        return [
            'product_total' => $productTotal,
            'discounts' => $discounts,
            'discount_fee' => $totalDiscountFee,
            'total_after_discount' => $totalAfterDiscount,
            'delivery_fee' => $deliveryFee,
            'final_total' => $finalTotal,
            'items' => $items,
            'detailed_products' => $detailedProducts,
        ];
    }

    /**
     * تحقق من وجود الرصيد للدفع بالمحفظة أو تمرير الحالة.
     */
    protected function handleWalletPayment(User $user, string $paymentMethod, float $amount): string
    {
        if ($paymentMethod === self::PAYMENT_WALLET) {
            if (!$this->walletService->hasSufficientBalance($user, $amount)) {
                throw ValidationException::withMessages([
                    'wallet' => __('order.wallet_insufficient')
                ]);
            }
            return self::STATUS_PENDING;
        }

        return self::STATUS_PENDING;
    }

    /**
     * إنشاء سجل الطلب في قاعدة البيانات.
     */
    protected function createOrder(int $userId, int $areaId, int $addressId, string $paymentMethod, ?string $notes, array $calculation, string $status): Order
    {
        return $this->orderRepo->create([
            'user_id' => $userId,
            'area_id' => $areaId,
            'address_id' => $addressId,
            'total_product_price' => $calculation['product_total'],
            'discount_fee'=>$calculation['discount_fee'],
            'totalAfterDiscount'=>$calculation['total_after_discount'],
            'delivery_fee' => $calculation['delivery_fee'],
            'total_price' => $calculation['final_total'],
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'status' => $status,
        ]);
    }

    /**
     * تخزين عناصر الطلب والخصوم في القواعد.
     */
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

    /**
     * توليد رسالة مناسبة حسب وسيلة الدفع والرصيد.
     */
    protected function getOrderMessage(string $paymentMethod, float $walletBalance, float $requiredAmount): ?string
    {
        if ($paymentMethod === self::PAYMENT_WALLET) {
            return $walletBalance >= $requiredAmount
                ? __('order.wallet_enough_but_not_deducted')
                : __('order.wallet_insufficient');
        }

        return null;
    }

    /**
     * عرض وسيلة الدفع بالعربية.
     */
    protected function getArabicPaymentMethod(string $method): string
    {
        return match ($method) {
            self::PAYMENT_WALLET => 'المحفظة',
            self::PAYMENT_CASH => 'نقدي',
            default => 'غير معروف',
        };
    }

    /**
     * عرض الطلبات الخاصة بمستخدم.
     */
    public function getUserOrders(int $userId, int $perPage = 10): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $orders = $this->orderRepo->getUserOrdersSortedByDate($userId, $perPage);
        return OrderResource::collection($orders);
    }


    /**
     * عرض تفاصيل طلب معين لمستخدم.
     */
    public function getUserOrderById(int $userId, int $orderId): ?OrderResource
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->with([
                'items.product',
                'items.store',
                'area',
                'orderDiscounts.discount',
                'address',
            ])
            ->first();

        return $order ? new OrderResource($order) : null;
    }


    public function getOrderStatusForUser(int $userId, int $orderId): ?array
    {
        $order = $this->orderRepo->getStatusForUser($userId, $orderId);
        return $order ? [
            'order_id'   => $order->id,
            'status'     => $order->status,
            'updated_at' => $order->updated_at,
        ] : null;
    }


    ///////////////////////////////////////for store/////////////////////////////

    /**
     * جلب الطلبات المُعلّقة الخاصة بمتجر معيّن (مقسمة صفحات).
     *
     * @param int $storeId
     * @param int $perPage
     * @return mixed
     */
    public function getPendingOrdersForStore(int $storeId, int $perPage = 10)
    {
        return $this->orderRepo->PendingOrdersForStore($storeId, $perPage);
    }

    /**
     * جلب الطلبات قيد التجهيز (مقسمة صفحات).
     *
     * @param int $storeId
     * @param int $perPage
     * @return mixed
     */
    public function getPreparingOrdersForStore(int $storeId, int $perPage = 10)
    {
        return $this->orderRepo->preparingOrdersForStore($storeId, $perPage);
    }

    /**
     * جلب الطلبات المُنجزة (مقسمة صفحات).
     *
     * @param int $storeId
     * @param int $perPage
     * @return mixed
     */
    public function getDoneOrdersForStore(int $storeId, int $perPage = 10)
    {
        return $this->orderRepo->DoneOrdersForStore($storeId, $perPage);
    }

    /**
     * جلب الطلبات التي رفضها هذا المتجر.
     */
    public function getRejectedOrdersForStore(int $storeId, int $perPage = 10)
    {
        return $this->orderRepo->getRejectedOrdersForStore($storeId , $perPage);
    }
    /**
     * تفاصيل طلب لمتجر محدد (تفاصيل مفردة ).
     *
     * @param int $orderId
     * @param int $storeId
     * @return mixed
     */
    public function getStoreOrderDetails(int $orderId, int $storeId)
    {
        return $this->orderRepo->getStoreOrderDetails($orderId, $storeId);
    }
    /**
     * يقوم المتجر بقبول المنتجات الخاصة به في طلب معيّن.
     *
     * - يتم تحديث حالة عناصر الطلب الخاصة بالمتجر إلى "مقبول".
     * - يتم تسجيل رد المتجر في جدول store_order_responses مع وقت الرد.
     *
     * @param int $orderId رقم تعريف الطلب
     * @param int $storeId رقم تعريف المتجر
     * @return array رسالة النجاح
     */
    public function acceptStoreItems(int $orderId, int $storeId): array
    {
        return DB::transaction(function () use ($orderId, $storeId) {
            $this->orderRepo->updateOrderItemsStatusForStore($orderId, $storeId, 'مقبول');

            $this->orderRepo->saveStoreResponse($orderId, $storeId, 'مقبول');

            return [
                'message' => 'تم قبول منتجات المتجر بنجاح.',
            ];
        });
    }



    /**
     * يقوم المتجر برفض المنتجات الخاصة به في طلب معيّن مع إمكانية إضافة سبب للرفض.
     *
     * - يتم تحديث حالة العناصر الخاصة بالمتجر إلى "مرفوض".
     * - يتم تسجيل حالة الرفض في جدول store_order_responses مع السبب ووقت الرد.
     *
     * @param int $orderId رقم تعريف الطلب
     * @param int $storeId رقم تعريف المتجر
     * @param string|null $reason سبب الرفض (اختياري)
     * @return array رسالة النجاح
     */
    public function rejectOrderByStore(int $orderId, int $storeId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($orderId, $storeId, $reason) {
            // تحديث حالة المنتجات الخاصة بالمتجر إلى مرفوض
            $this->orderRepo->updateOrderItemsStatusForStore($orderId, $storeId, 'مرفوض');

            // حفظ رد المتجر مع السبب
            $storeTotalInvoice = $this->orderRepo->saveStoreResponse($orderId, $storeId, 'مرفوض', $reason);

            // 🧮 إعادة حساب قيم الطلب بعد خصم منتجات هذا المتجر
            $order = Order::with(['items', 'items.product'])->findOrFail($orderId);

            // استبعاد المنتجات الخاصة بالمتجر الرافض
            $includedItems = $order->items->where('store_id', '!=', $storeId)->where('status', '!=', 'مرفوض');

            $newProductTotal = $includedItems->sum('total_price');
            $newDiscountFee = $includedItems->sum('discount_value');
            $totalAfterDiscount = $newProductTotal - $newDiscountFee;
            $deliveryFee = $totalAfterDiscount >= optional($order->area)->free_delivery_from ? 0 : optional($order->area)->delivery_fee;
            $finalTotal = $totalAfterDiscount + $deliveryFee;

            // تحديث الطلب بالقيم الجديدة
            $order->update([
                'total_product_price' => $newProductTotal,
                'discount_fee' => $newDiscountFee,
                'totalAfterDiscount' => $totalAfterDiscount,
                'delivery_fee' => $deliveryFee,
                'total_price' => $finalTotal,
            ]);

            return [
                'message' => 'تم رفض منتجات المتجر، وتحديث فاتورة الطلب.',
            ];
        });
    }



}
