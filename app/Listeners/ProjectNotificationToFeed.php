<?php

// app/Listeners/ProjectNotificationToFeed.php
namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;

class ProjectNotificationToFeed
{
    public function handle(NotificationSent $event): void
    {
        // اسقط فقط إشعارات المستخدمين (Notifiable = User)
        if (! $event->notifiable instanceof \App\Models\User) return;

        $payload = method_exists($event->notification, 'toArray')
            ? $event->notification->toArray($event->notifiable)
            : [];

        DB::table('app_user_notifications')->insert([
            'user_id'    => $event->notifiable->getKey(),
            'type'       => $payload['type']  ?? class_basename($event->notification),
            'title'      => $payload['title'] ?? 'إشعار',
            'body'       => $payload['body']  ?? null,
            'order_id'   => $payload['order_id'] ?? null,
            'data'       => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
