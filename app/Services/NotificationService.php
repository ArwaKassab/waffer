<?php

namespace App\Services;

use App\Models\AppUserNotification;
use App\Models\DeviceToken;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function __construct(protected FcmV1Client $fcm) {}

    public function sendToUser(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?int $orderId = null,
        array $data = [],
        ?string $appKey = null // ← جديد
    ): AppUserNotification {
        $row = AppUserNotification::create([
            'user_id'  => $userId,
            'type'     => $type,
            'title'    => $title,
            'body'     => $body,
            'order_id' => $orderId,
            'data'     => $data,
            'read_at'  => null,
        ]);

        $tokensQuery = DeviceToken::where('user_id', $userId);

        if ($appKey) {
            $tokensQuery->where('app_key', $appKey);
        }

        $tokens = $tokensQuery->pluck('token')->filter()->unique()->all();

        foreach ($tokens as $token) {
            $this->fcm->sendToToken(
                $token,
                $title,
                $body ?? '',
                [
                    'notification_id' => (string) $row->id,
                    'type'            => $type,
                    'order_id'        => $orderId ? (string) $orderId : '',
                ] + $data
            );
        }

        return $row;
    }

    public function markAllReadForUser(int $userId): void
    {
        AppUserNotification::where('user_id', $userId)->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function markOneRead(int $userId, int $notificationId): void
    {
        AppUserNotification::where('user_id', $userId)->where('id', $notificationId)->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function unreadCountForUser(int $userId): int
    {
        return AppUserNotification::where('user_id', $userId)->whereNull('read_at')->count();
    }
}
