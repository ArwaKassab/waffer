<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FcmV1Client;
use Illuminate\Support\Facades\Log;

class FcmV1Channel
{
    public function __construct(protected FcmV1Client $fcm) {}

    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) return;

        $tokens = method_exists($notifiable, 'routeNotificationForFcm')
            ? ($notifiable->routeNotificationForFcm() ?? [])
            : [];

        if (!$tokens) return;

        $p     = $notification->toFcm($notifiable);
        $title = $p['notification']['title'] ?? '';
        $body  = $p['notification']['body']  ?? '';
        $data  = $p['data'] ?? [];

        foreach ($tokens as $token) {
            try {
                $this->fcm->sendToToken($token, $title, $body, $data);
            } catch (\Throwable $e) {
                Log::error('FCM send error (single token)', ['msg' => $e->getMessage()]);
            }
        }
    }
}
