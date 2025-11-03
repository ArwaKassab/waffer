<?php


namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notifications
    )
    {
    }

    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        $this->notifications->sendToUser(
            userId: $event->customerUserId,
            type: 'order_status_changed',
            title: "تحديث على طلبك #{$order->id}",
            body: "حالة الطلب الآن: {$order->status}",
            orderId: $order->id,
            data: [
                'status' => $order->status,
                'store_name' => $order->store->name ?? '',
            ]
        );
    }
}

//
//namespace App\Listeners;
//
//use App\Events\OrderStatusUpdated;
//use App\Notifications\OrderStatusChanged;
//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Queue\InteractsWithQueue;
//
//class SendOrderStatusNotification
//{
//    public function handle(\App\Events\OrderStatusUpdated $e): void
//    {
//        $user = \App\Models\User::find($e->userId);
//        if (!$user) return;
//
//        $user->notify(new OrderStatusChanged($e->orderId, $e->status));
//    }
//}
