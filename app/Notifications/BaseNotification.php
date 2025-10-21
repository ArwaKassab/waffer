<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\FcmV1Channel;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        $via = ['database']; // اكتب في DB دائمًا أولًا

        $hasProject = (bool) config('services.fcm_v1.project_id');
        $hasTokens  = method_exists($notifiable, 'routeNotificationForFcm')
            && !empty($notifiable->routeNotificationForFcm());

        if ($hasProject && $hasTokens) {
            $via[] = FcmV1Channel::class;
        }
        return $via;
    }
}
