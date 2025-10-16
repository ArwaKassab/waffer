<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\FcmV1Channel;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** القنوات الافتراضية: Database + FCM v1 */
    public function via($notifiable): array
    {
        return ['database', FcmV1Channel::class];
    }

    /** اختياري: اختصارات للترجمة */
    protected function title(string $key, array $params = []): string
    {
        return __($key, $params);
    }

    protected function body(string $key, array $params = []): string
    {
        return __($key, $params);
    }
}
