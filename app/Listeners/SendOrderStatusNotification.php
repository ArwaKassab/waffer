<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Notifications\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusNotification
{
    public function handle(\App\Events\OrderStatusUpdated $e): void
    {
        $user = \App\Models\User::find($e->userId);
        if (!$user) return;

        $user->notify(new OrderStatusChanged($e->orderId, $e->status));
        // إن حبيتي تفصلي الـPush لJob مستقل، تقدري هنا dispatch Job للـFCM فقط.
    }
}
