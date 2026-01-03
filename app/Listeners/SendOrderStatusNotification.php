<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderStatusNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        Log::info('SendOrderStatusNotification: started', [
            'order_id' => $order->id,
            'user_id'  => $event->customerUserId,
            'status'   => $order->status,
        ]);

        [$title, $body] = $this->buildUserMessage($order->status, (int) $order->id);

        $this->notifications->sendToUser(
            userId: $event->customerUserId,
            type: 'order_status_changed',
            title: $title,
            body: $body,
            orderId: (int) $order->id,
            data: [
                'status' => (string) $order->status,
            ]
        );

        Log::info('SendOrderStatusNotification: finished', [
            'order_id' => $order->id,
        ]);
    }

    /**
     * يبني عنوان/نص واضح ولطيف للمستخدم حسب الحالة.
     * ملاحظة: غيّري النصوص لتطابق حالاتك الحقيقية (انتظار/مقبول/يجهز/حضر/مرفوض/تم التوصيل...).
     */
    private function buildUserMessage(string $status, int $orderId): array
    {
        $status = trim($status);

        return match ($status) {
            'انتظار' => [
                'تم استلام طلبك',
                "طلبك رقم #{$orderId} وصلنا، وعم نراجعه هلّق.",
            ],
            'مقبول' => [
                'تم قبول طلبك',
                "ممتاز! طلبك رقم #{$orderId} تم قبوله وبلّش التجهيز.",
            ],
            'يجهز' => [
                'طلبك قيد التجهيز',
                "طلبك رقم #{$orderId} قيد التجهيز. سنبلغك أول ما يصير جاهز.",
            ],
            'حضر' => [
                'طلبك صار جاهز',
                "طلبك رقم #{$orderId} صار جاهز. تابع تفاصيله من صفحة الطلب.",
            ],
            'مستلم' => [
                'طلبك صار عندك',
                "طلبك رقم #{$orderId} صار عندك. تابع تفاصيله من صفحة الطلب.",
            ],
            'مرفوض' => [
                'تعذر تنفيذ طلبك',
                "للأسف، تم رفض طلبك رقم #{$orderId}. تقدر تشوف السبب من تفاصيل الطلب.",
            ],
            default => [
                'تحديث على طلبك',
                "تم تحديث حالة طلبك رقم #{$orderId} إلى: {$status}.",
            ],
        };
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
