<?php

namespace App\Listeners;

use App\Events\NewOrderCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewOrderNotificationToStores implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationService $notificationService) {}

    public function handle(NewOrderCreated $event)
    {
        $order = $event->order->loadMissing(['items', 'user']);

        $storeIds = $order->items
            ->pluck('store_id')
            ->unique()
            ->values();

        foreach ($storeIds as $storeUserId) {
            $this->notificationService->sendToUser(
                userId: $storeUserId,
                type: 'new_order_for_store',
                title: 'طلب جديد',
                body: "وصلك طلب جديد رقم {$order->id}",
                orderId: $order->id,
                data: [
                    'customer'    => $order->user->name ?? '',
                ]
            );
        }
    }
}
