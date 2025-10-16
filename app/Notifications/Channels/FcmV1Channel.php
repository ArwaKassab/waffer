<?php

// app/Notifications/Channels/FcmV1Channel.php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FcmV1Client;

class FcmV1Channel {
    public function __construct(protected FcmV1Client $fcm) {}

    public function send($notifiable, Notification $notification): void {
        if (!method_exists($notification, 'toFcm')) return;

        $tokens = $notifiable->routeNotificationForFcm();
        if (empty($tokens)) return;

        $payload = $notification->toFcm($notifiable);
        $title = $payload['notification']['title'] ?? '';
        $body  = $payload['notification']['body']  ?? '';
        $data  = $payload['data'] ?? [];

        foreach ($tokens as $token) {
            $this->fcm->sendToToken($token, $title, $body, $data);
        }
    }
}
