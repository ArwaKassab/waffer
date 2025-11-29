<?php

namespace App\Listeners;

use App\Events\StoreProductDiscountAdded;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySubAdminProductDiscountAdded implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function handle(StoreProductDiscountAdded $event): void
    {
        $store = $event->store;

        // نجيب أدمن المنطقة
        $subAdmins = User::query()
            ->where('type', 'sub_admin')
            ->where('area_id', $store->area_id)
            ->get();

        if ($subAdmins->isEmpty()) {
            return;
        }

        $product = $event->product;
        $discount = $event->discount;

        // عنوان/نص الإشعار
        $title = "خصم جديد على منتج";
        $body  = "قام المتجر {$store->name} بإضافة خصم على المنتج {$product->name}.";

        foreach ($subAdmins as $admin) {
            $this->notifications->sendToUser(
                userId: $admin->id,
                type: 'store_product_discount_added',
                title: $title,
                body: $body,
                orderId: null,
                data: [
                    'product_id'  => (string)$product->id,
                    'discount_id' => (string)$discount->id,
                    'store_id'    => (string)$store->id,
                ],
            );
        }
    }
}
