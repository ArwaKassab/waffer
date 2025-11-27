<?php

namespace App\Listeners;

use App\Events\StoreProductChanged;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendStoreProductChangedToSubAdmins implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function handle(StoreProductChanged $event): void
    {
        $store = $event->store;

        if (!$store->area_id) {
            return;
        }

        $subAdmins = User::where('type', 'sub_admin')
            ->where('area_id', $store->area_id)
            ->get();

        if ($subAdmins->isEmpty()) {
            return;
        }

        $type = $event->action === 'direct_update'
            ? 'store_product_direct_updated'
            : 'store_product_direct_deleted';

        $title = $event->action === 'direct_update'
            ? 'تعديل منتج من متجر'
            : 'حذف منتج من متجر';

        $body = $event->action === 'direct_update'
            ? "قام المتجر {$store->name} بتعديل المنتج {$event->product->name}."
            : "قام المتجر {$store->name} بحذف المنتج {$event->product->name}.";

        foreach ($subAdmins as $admin) {
            $this->notifications->sendToUser(
                userId: $admin->id,
                type: $type,
                title: $title,
                body: $body,
                orderId: null,
                data: [
                    'action'      => $event->action,
                    'product_id'  => (string) $event->product->id,
                    'store_id'    => (string) $store->id,
                    'store_name'  => $store->name,
                ]
            );
        }
    }
}
