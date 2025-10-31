<?php

namespace App\Services;

use App\Models\AppUserNotification;
use App\Models\DeviceToken;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function __construct(
        protected FcmV1Client $fcm
    ) {}

    /**
     * sendToUser:
     * - يسجل الإشعار في قاعدة البيانات
     * - يبعته Push لكل أجهزة المستخدم
     */
    public function sendToUser(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?int $orderId = null,
        array $data = []
    ): AppUserNotification {

        // 1) خزّن الإشعار
        $row = AppUserNotification::create([
            'user_id'  => $userId,
            'type'     => $type,
            'title'    => $title,
            'body'     => $body,
            'order_id' => $orderId,
            'data'     => $data,
            'read_at'  => null,
        ]);

        // 2) جيب كل توكنات الأجهزة تبع هالمستخدم
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->unique()
            ->all();

        // 3) ابعث Push لكل جهاز
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
        AppUserNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function markOneRead(int $userId, int $notificationId): void
    {
        AppUserNotification::where('user_id', $userId)
            ->where('id', $notificationId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function unreadCountForUser(int $userId): int
    {
        return AppUserNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
