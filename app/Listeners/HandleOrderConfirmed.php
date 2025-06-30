<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleOrderConfirmed implements ShouldQueue
{
    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order;

        // مثال: إرسال إشعار أو رسالة للمستخدم أو البريد
        \Log::info("تم تأكيد الطلب رقم {$order->id}.");

        // يمكنك هنا:
        // - إرسال إشعار push
        // - إرسال بريد إلكتروني
        // - إشعار مسؤول
    }
}
